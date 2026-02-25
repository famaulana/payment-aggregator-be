<?php

namespace App\Services\Payment;

use App\DTOs\CreatePaymentDTO;
use App\DTOs\RefundDTO;
use App\Enums\RefundStatus;
use App\Enums\TransactionStatus;
use App\Enums\WebhookEvent;
use App\Models\PaymentRefund;
use App\Models\PaymentGatewayLog;
use App\Models\Transaction;
use App\Services\Gateway\GatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private PaymentRouterService    $router,
        private MdrCalculatorService    $mdrCalculator,
        private WebhookDispatcherService $webhookDispatcher,
        private BalanceService          $balanceService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Create Payment
    // ─────────────────────────────────────────────────────────────────────────

    public function createPayment(CreatePaymentDTO $dto): Transaction
    {
        [$paymentMethod, $mapping] = $this->router->route($dto->paymentMethod, $dto->paymentChannel);
        $gateway = $mapping->paymentGateway;

        // Validate amount against channel limits (mapping overrides method-level limits)
        $minAmount = $mapping->min_amount ?? $paymentMethod->min_amount;
        $maxAmount = $mapping->max_amount ?? $paymentMethod->max_amount;

        if ($minAmount && $dto->amount < $minAmount) {
            throw new \InvalidArgumentException(
                "Amount {$dto->amount} is below the minimum of {$minAmount} for {$paymentMethod->method_name}."
            );
        }

        if ($maxAmount && $dto->amount > $maxAmount) {
            throw new \InvalidArgumentException(
                "Amount {$dto->amount} exceeds the maximum of {$maxAmount} for {$paymentMethod->method_name}."
            );
        }

        // Calculate fee breakdown
        $fees = $this->mdrCalculator->calculate($mapping, $dto->amount);

        // Create Transaction record
        $transaction = DB::transaction(function () use ($dto, $paymentMethod, $mapping, $gateway, $fees) {
            $expiredAt = $dto->expiredAt
                ? new \DateTime($dto->expiredAt)
                : now()->addHours(24);

            return Transaction::create([
                'client_id'          => $dto->clientId,
                'merchant_id'        => $dto->merchantId,
                'transaction_id'     => $dto->transactionId,
                'merchant_ref'       => $dto->merchantRef,
                'currency'           => $dto->currency,
                'payment_method_id'  => $paymentMethod->id,
                'payment_gateway_id' => $gateway->id,
                'gross_amount'       => $dto->amount,
                'vendor_margin'      => $fees['vendor_margin'],
                'our_margin'         => $fees['our_margin'],
                'mdr_amount'         => $fees['mdr_amount'],
                'net_amount'         => $fees['net_amount'],
                'refunded_amount'    => 0,
                'status'             => TransactionStatus::PENDING,
                'original_status'    => TransactionStatus::PENDING,
                'customer_name'      => $dto->customerName,
                'customer_email'     => $dto->customerEmail,
                'customer_phone'     => $dto->customerPhone,
                'payment_type'       => $paymentMethod->method_type->value,
                'expired_at'         => $expiredAt,
                'callback_url'       => $dto->callbackUrl,
                'redirect_url'       => $dto->redirectUrl,
                'items'              => $dto->items,
                'metadata'           => $dto->metadata,
            ]);
        });

        // Call gateway
        $startTime = microtime(true);
        $result = GatewayFactory::make($gateway)->createPayment($dto);
        $processingMs = (int) ((microtime(true) - $startTime) * 1000);

        // Log gateway call
        PaymentGatewayLog::create([
            'transaction_id'     => $transaction->id,
            'payment_gateway_id' => $gateway->id,
            'action'             => 'create_payment',
            'request_url'        => $gateway->getApiUrl(),
            'request_method'     => 'POST',
            'request_body'       => ['transaction_id' => $dto->transactionId, 'amount' => $dto->amount],
            'response_status'    => $result->success ? 200 : 500,
            'response_body'      => $result->rawResponse,
            'processing_time_ms' => $processingMs,
            'is_success'         => $result->success,
        ]);

        if (!$result->success) {
            $transaction->update(['status' => TransactionStatus::FAILED]);
            throw new \RuntimeException($result->errorMessage ?? 'Payment gateway error');
        }

        // Update transaction with PG data
        $transaction->update(array_filter([
            'pg_reference_id'  => $result->pgReferenceId,
            'pg_va_number'     => $result->vaNumber,
            'pg_qr_string'     => $result->qrString,
            'pg_checkout_url'  => $result->checkoutUrl,
            'pg_deeplink_url'  => $result->deeplinkUrl,
            'account_number'   => $result->vaNumber,
        ], fn($v) => $v !== null));

        // Dispatch pending webhook — load with client for webhook signing
        $this->webhookDispatcher->dispatch($transaction->fresh()->load('client'), WebhookEvent::PAYMENT_PENDING);

        return $transaction->fresh()->load(['paymentMethod', 'paymentGateway', 'merchant']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Process Inbound Webhook (status update from PG)
    // ─────────────────────────────────────────────────────────────────────────

    public function processStatusUpdate(Transaction $transaction, string $newStatus): void
    {
        $statusEnum = TransactionStatus::from($newStatus);

        if ($transaction->status->isTerminal()) {
            Log::warning('[PaymentService] Attempted to update terminal transaction', [
                'transaction_id' => $transaction->transaction_id,
                'current_status' => $transaction->status->value,
                'new_status'     => $newStatus,
            ]);
            return;
        }

        $updates = ['status' => $statusEnum];

        if ($statusEnum === TransactionStatus::PAID) {
            $updates['paid_at'] = now();
        }

        $transaction->update($updates);

        // Record balance movement when transaction is paid
        if ($statusEnum === TransactionStatus::PAID) {
            $this->balanceService->recordPayment($transaction->fresh()->load('client'));
        }

        $event = match ($statusEnum) {
            TransactionStatus::PAID     => WebhookEvent::PAYMENT_PAID,
            TransactionStatus::FAILED   => WebhookEvent::PAYMENT_FAILED,
            TransactionStatus::EXPIRED  => WebhookEvent::PAYMENT_EXPIRED,
            TransactionStatus::REFUNDED => WebhookEvent::PAYMENT_REFUNDED,
            default => null,
        };

        if ($event) {
            $this->webhookDispatcher->dispatch($transaction->fresh()->load('client'), $event);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cancel Payment
    // ─────────────────────────────────────────────────────────────────────────

    public function cancelPayment(Transaction $transaction): bool
    {
        if ($transaction->status !== TransactionStatus::PENDING) {
            throw new \RuntimeException('Only pending payments can be cancelled.');
        }

        $gateway = GatewayFactory::make($transaction->paymentGateway);
        $cancelled = $gateway->cancelPayment($transaction->pg_reference_id);

        if ($cancelled) {
            $transaction->update(['status' => TransactionStatus::FAILED]);
        }

        return $cancelled;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Refund
    // ─────────────────────────────────────────────────────────────────────────

    public function refund(Transaction $transaction, float $amount, string $reason, ?string $refId): PaymentRefund
    {
        if (!$transaction->canBeRefunded()) {
            throw new \RuntimeException('Transaction cannot be refunded.');
        }

        if ($amount > $transaction->getRemainingRefundableAmount()) {
            throw new \RuntimeException('Refund amount exceeds refundable amount.');
        }

        $refundId = 'RFN-' . date('Ymd') . '-' . strtoupper(uniqid());

        // Check idempotency
        if ($refId) {
            $existing = PaymentRefund::where('ref_id', $refId)->first();
            if ($existing) {
                return $existing;
            }
        }

        $refund = DB::transaction(function () use ($transaction, $amount, $reason, $refId, $refundId) {
            $dto = new RefundDTO(
                transactionId: $transaction->transaction_id,
                pgReferenceId: $transaction->pg_reference_id,
                amount:        $amount,
                reason:        $reason,
                refId:         $refId,
            );

            $gateway = GatewayFactory::make($transaction->paymentGateway);
            $result  = $gateway->refund($dto);

            $refund = PaymentRefund::create([
                'transaction_id' => $transaction->id,
                'refund_id'      => $refundId,
                'pg_refund_id'   => $result->pgRefundId,
                'amount'         => $amount,
                'reason'         => $reason,
                'status'         => $result->success ? RefundStatus::PENDING : RefundStatus::FAILED,
                'ref_id'         => $refId,
                'pg_response'    => $result->rawResponse,
            ]);

            if ($result->success) {
                $newRefunded = (float) $transaction->refunded_amount + $amount;
                $isFullRefund = $newRefunded >= (float) $transaction->gross_amount;

                $transaction->update([
                    'refunded_amount' => $newRefunded,
                    'status' => $isFullRefund ? TransactionStatus::REFUNDED : $transaction->status,
                ]);

                // Deduct refunded amount from client balance
                $this->balanceService->recordRefund($transaction->fresh()->load('client'), $amount);

                if ($isFullRefund) {
                    $this->webhookDispatcher->dispatch(
                        $transaction->fresh()->load('client'),
                        WebhookEvent::PAYMENT_REFUNDED
                    );
                }
            }

            return $refund;
        });

        return $refund;
    }
}
