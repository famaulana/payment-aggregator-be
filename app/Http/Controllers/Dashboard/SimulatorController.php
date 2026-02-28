<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ResponseCode;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use App\Services\Payment\SimulatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SimulatorController extends Controller
{
    public function __construct(
        private PaymentService   $paymentService,
        private SimulatorService $simulatorService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET /dashboard/simulator/transactions
    // List transactions — scoped to client if not system owner
    // ─────────────────────────────────────────────────────────────────────────

    public function transactions(Request $request): JsonResponse
    {
        $user  = auth()->user();

        // Simulator only accessible by System Owner and Client
        if (!$user->isSystemOwner() && !$user->isClientUser()) {
            return $this->forbidden('Simulator is only accessible by System Owner and Client users');
        }

        $query = Transaction::with(['paymentMethod', 'paymentGateway', 'merchant', 'client']);

        // Client scoping - Client users can only see their own transactions
        if ($user->isClientUser()) {
            $query->where('client_id', $user->getClientId());
        } elseif ($request->filled('client_id')) {
            // System Owner can filter by client_id
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->whereHas('paymentMethod', function ($q) use ($request) {
                $q->where('method_type', $request->payment_method)
                    ->orWhere('method_code', $request->payment_method);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('merchant_ref', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByDesc('created_at')->paginate(20);

        $transactions->setCollection(
            $transactions->getCollection()->map(fn($t) => $this->formatTransaction($t))
        );

        return $this->pagination($transactions, 'Transactions retrieved');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /dashboard/simulator/transactions/{transactionId}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->resolveTransaction($transactionId);

        if (!$transaction) {
            return $this->notFound('Transaction not found');
        }

        if (!$this->canAccess($transaction)) {
            return $this->forbidden();
        }

        return $this->success($this->formatTransaction($transaction, detailed: true));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /dashboard/simulator/transactions/{transactionId}/pay
    // Simulate a successful payment from the customer
    // ─────────────────────────────────────────────────────────────────────────

    public function pay(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->resolveTransaction($transactionId);

        if (!$transaction) {
            return $this->notFound('Transaction not found');
        }

        if (!$this->canAccess($transaction)) {
            return $this->forbidden();
        }

        if ($transaction->status !== TransactionStatus::PENDING) {
            $hint = config('app.simulator_auto_success') && !app()->isProduction()
                ? ' Hint: SIMULATOR_AUTO_SUCCESS=true means payment was auto-paid at creation.'
                : '';
            return $this->error(
                ResponseCode::VALIDATION_ERROR,
                "Cannot simulate pay: status is [{$transaction->status->value}], must be [pending].{$hint}"
            );
        }

        try {
            $result = $this->simulatorService->trigger($transaction, 'payment.paid');

            Log::info('[Simulator] Simulated PAID', [
                'transaction_id' => $transaction->transaction_id,
                'simulated_by'   => auth()->id(),
            ]);

            return $this->success([
                'transaction' => $this->formatTransaction($result['transaction']),
                'webhooks'    => [
                    'inbound'  => $result['inbound'],
                    'outbound' => $result['outbound'],
                ],
            ], 'Payment simulated as PAID. Full webhook cycle executed.');
        } catch (\Throwable $e) {
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /dashboard/simulator/transactions/{transactionId}/fail
    // ─────────────────────────────────────────────────────────────────────────

    public function fail(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->resolveTransaction($transactionId);

        if (!$transaction) {
            return $this->notFound('Transaction not found');
        }

        if (!$this->canAccess($transaction)) {
            return $this->forbidden();
        }

        if ($transaction->status !== TransactionStatus::PENDING) {
            return $this->error(
                ResponseCode::VALIDATION_ERROR,
                "Cannot simulate fail: status is [{$transaction->status->value}], must be [pending]."
            );
        }

        try {
            $result = $this->simulatorService->trigger($transaction, 'payment.failed');

            Log::info('[Simulator] Simulated FAILED', [
                'transaction_id' => $transaction->transaction_id,
                'simulated_by'   => auth()->id(),
            ]);

            return $this->success([
                'transaction' => $this->formatTransaction($result['transaction']),
                'webhooks'    => [
                    'inbound'  => $result['inbound'],
                    'outbound' => $result['outbound'],
                ],
            ], 'Payment simulated as FAILED. Full webhook cycle executed.');
        } catch (\Throwable $e) {
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /dashboard/simulator/transactions/{transactionId}/expire
    // ─────────────────────────────────────────────────────────────────────────

    public function expire(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->resolveTransaction($transactionId);

        if (!$transaction) {
            return $this->notFound('Transaction not found');
        }

        if (!$this->canAccess($transaction)) {
            return $this->forbidden();
        }

        if ($transaction->status !== TransactionStatus::PENDING) {
            return $this->error(
                ResponseCode::VALIDATION_ERROR,
                "Cannot simulate expire: status is [{$transaction->status->value}], must be [pending]."
            );
        }

        try {
            $result = $this->simulatorService->trigger($transaction, 'payment.expired');

            Log::info('[Simulator] Simulated EXPIRED', [
                'transaction_id' => $transaction->transaction_id,
                'simulated_by'   => auth()->id(),
            ]);

            return $this->success([
                'transaction' => $this->formatTransaction($result['transaction']),
                'webhooks'    => [
                    'inbound'  => $result['inbound'],
                    'outbound' => $result['outbound'],
                ],
            ], 'Payment simulated as EXPIRED. Full webhook cycle executed.');
        } catch (\Throwable $e) {
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /dashboard/simulator/transactions/{transactionId}/refund
    // Simulate full refund — goes through proper PaymentService refund flow
    // ─────────────────────────────────────────────────────────────────────────

    public function refund(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->resolveTransaction($transactionId);

        if (!$transaction) {
            return $this->notFound('Transaction not found');
        }

        if (!$this->canAccess($transaction)) {
            return $this->forbidden();
        }

        if ($transaction->status !== TransactionStatus::PAID) {
            return $this->error(
                ResponseCode::VALIDATION_ERROR,
                "Cannot simulate refund: transaction status is [{$transaction->status->value}], must be [paid]"
            );
        }

        if (!$transaction->canBeRefunded()) {
            return $this->error(ResponseCode::VALIDATION_ERROR, 'Transaction has already been fully refunded.');
        }

        $amount = $request->input('amount', $transaction->getRemainingRefundableAmount());
        $reason = $request->input('reason', 'Simulator refund');

        try {
            $refund = $this->paymentService->refund($transaction, (float) $amount, $reason, null);

            Log::info('[Simulator] Simulated REFUND', [
                'transaction_id' => $transaction->transaction_id,
                'refund_id'      => $refund->refund_id,
                'amount'         => $amount,
                'simulated_by'   => auth()->id(),
            ]);

            return $this->success([
                'refund'      => [
                    'refund_id'    => $refund->refund_id,
                    'amount'       => $refund->amount,
                    'status'       => $refund->status->value,
                    'reason'       => $refund->reason,
                    'processed_at' => $refund->created_at?->toDateTimeString(),
                ],
                'transaction' => $this->formatTransaction(
                    $transaction->fresh()->load(['paymentMethod', 'paymentGateway', 'merchant', 'client'])
                ),
            ], 'Refund simulated. Outbound webhook dispatched if full refund.');
        } catch (\Throwable $e) {
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveTransaction(string $transactionId): ?Transaction
    {
        return Transaction::with(['paymentMethod', 'paymentGateway', 'merchant', 'client'])
            ->where('transaction_id', $transactionId)
            ->first();
    }

    private function canAccess(Transaction $transaction): bool
    {
        $user = auth()->user();

        // Simulator is only accessible by System Owner and Client
        if (!$user->isSystemOwner() && !$user->isClientUser()) {
            return false;
        }

        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->isClientUser()) {
            return $transaction->client_id === $user->getClientId();
        }

        return false;
    }

    private function formatTransaction(Transaction $t, bool $detailed = false): array
    {
        $method  = $t->paymentMethod;
        $gateway = $t->paymentGateway;

        $data = [
            'transaction_id'   => $t->transaction_id,
            'merchant_ref'     => $t->merchant_ref,
            'status'           => $t->status->value,
            'payment_method'   => [
                'type'    => $method?->method_type?->value,
                'channel' => $method?->method_code,
                'name'    => $method?->method_name,
            ],
            'gateway'          => $gateway?->pg_code,
            'amount'           => [
                'gross'  => (float) $t->gross_amount,
                'mdr'    => (float) $t->mdr_amount,
                'net'    => (float) $t->net_amount,
                'refunded' => (float) $t->refunded_amount,
            ],
            'customer'         => [
                'name'  => $t->customer_name,
                'email' => $t->customer_email,
                'phone' => $t->customer_phone,
            ],
            'payment_data'     => $this->resolvePaymentData($t),
            'actions'          => $this->availableActions($t),
            'timestamps'       => [
                'created_at'  => $t->created_at?->toDateTimeString(),
                'expired_at'  => $t->expired_at?->toDateTimeString(),
                'paid_at'     => $t->paid_at?->toDateTimeString(),
            ],
        ];

        if ($detailed) {
            $data['pg_reference_id']  = $t->pg_reference_id;
            $data['merchant']         = $t->merchant ? [
                'id'   => $t->merchant->id,
                'code' => $t->merchant->merchant_code,
                'name' => $t->merchant->merchant_name,
            ] : null;
            $data['client']           = $t->client ? [
                'id'   => $t->client->id,
                'code' => $t->client->client_code,
                'name' => $t->client->client_name,
            ] : null;
            $data['callback_url']     = $t->callback_url;
            $data['redirect_url']     = $t->redirect_url;
            $data['metadata']         = $t->metadata;
            $data['refunds']          = $t->refunds?->map(fn($r) => [
                'refund_id'  => $r->refund_id,
                'amount'     => (float) $r->amount,
                'status'     => $r->status->value,
                'reason'     => $r->reason,
                'created_at' => $r->created_at?->toDateTimeString(),
            ]);
        }

        return $data;
    }

    /**
     * Return payment-method-specific display data
     */
    private function resolvePaymentData(Transaction $t): array
    {
        $type = $t->paymentMethod?->method_type?->value;

        return match ($type) {
            'virtual_account' => [
                'type'      => 'virtual_account',
                'va_number' => $t->pg_va_number,
                'bank'      => $t->paymentMethod?->method_name,
                'how_to_pay' => 'Transfer sejumlah gross_amount ke nomor VA di atas, lalu tekan "Simulate Pay".',
            ],
            'qris' => [
                'type'      => 'qris',
                'qr_string' => $t->pg_qr_string,
                'qr_url'    => $t->pg_checkout_url,
                'how_to_pay' => 'Scan QR code dengan aplikasi pembayaran, lalu tekan "Simulate Pay".',
            ],
            'e_wallet' => [
                'type'         => 'e_wallet',
                'checkout_url' => $t->pg_checkout_url,
                'deeplink_url' => $t->pg_deeplink_url,
                'how_to_pay'   => 'Buka checkout_url atau deeplink_url di app e-wallet, lalu tekan "Simulate Pay".',
            ],
            'paylater' => [
                'type'         => 'paylater',
                'checkout_url' => $t->pg_checkout_url,
                'how_to_pay'   => 'Buka checkout_url untuk proses persetujuan cicilan, lalu tekan "Simulate Pay".',
            ],
            default => [],
        };
    }

    /**
     * Return available simulator actions based on current status
     */
    private function availableActions(Transaction $t): array
    {
        return match ($t->status) {
            TransactionStatus::PENDING => ['pay', 'fail', 'expire'],
            TransactionStatus::PAID    => $t->canBeRefunded() ? ['refund'] : [],
            default                    => [],
        };
    }
}
