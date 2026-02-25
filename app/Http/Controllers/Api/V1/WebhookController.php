<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResponseCode;
use App\Enums\TransactionStatus;
use App\Enums\WebhookDirection;
use App\Enums\WebhookEvent;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundWebhook;
use App\Jobs\DispatchOutboundWebhook;
use App\Models\PaymentGateway;
use App\Models\PaymentWebhookLog;
use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use App\Services\Payment\SimulatorService;
use App\Services\Payment\WebhookDispatcherService;
use App\Services\Shared\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentService          $paymentService,
        private WebhookDispatcherService $webhookDispatcher,
        private SimulatorService        $simulatorService,
    ) {}
    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/webhooks/inbound/{gateway} — Inbound from PG (no client auth)
    // ─────────────────────────────────────────────────────────────────────────

    public function inbound(Request $request, string $gateway): JsonResponse
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        // Log inbound webhook immediately
        $log = PaymentWebhookLog::create([
            'direction'    => WebhookDirection::INBOUND,
            'gateway_code' => $gateway,
            'payload'      => $payload,
            'attempt_count' => 1,
            'is_verified'  => false,
        ]);

        // Validate gateway exists
        $pgGateway = PaymentGateway::where('pg_code', $gateway)
            ->where('status', 'active')
            ->first();

        if (!$pgGateway) {
            Log::warning("[Webhook] Unknown gateway: {$gateway}");
            return response()->json(['status' => 'ok'], 200); // Always 200 to PG
        }

        // Dispatch async processing
        ProcessInboundWebhook::dispatch($log->id, $gateway, $payload, $headers)
            ->onQueue('webhooks');

        return response()->json(['status' => 'received'], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/webhooks/test — Test webhook delivery to client
    // ─────────────────────────────────────────────────────────────────────────

    public function test(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $callbackUrl = $request->input('callback_url')
            ?? $client->settlement_config['callback_url']
            ?? null;

        if (!$callbackUrl) {
            return ResponseService::validationError(['callback_url' => ['No callback URL configured.']]);
        }

        $testPayload = [
            'event'      => WebhookEvent::PAYMENT_PAID->value,
            'event_id'   => 'EVT-TEST-' . strtoupper(uniqid()),
            'created_at' => now()->toIso8601String(),
            'data'       => [
                'transaction_id' => 'TXN-TEST-' . strtoupper(uniqid()),
                'merchant_ref'   => 'TEST-ORDER-001',
                'status'         => 'paid',
                'amount'         => 150000,
                'paid_at'        => now()->toIso8601String(),
            ],
            'signature'  => 'test-signature',
        ];

        DispatchOutboundWebhook::dispatch(null, $callbackUrl, $testPayload, true)
            ->onQueue('webhooks');

        return ResponseService::success([
            'message'      => 'Test webhook dispatched',
            'callback_url' => $callbackUrl,
            'payload'      => $testPayload,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/webhooks/simulator/transactions
    // List the authenticated client's transactions for simulator use
    // ─────────────────────────────────────────────────────────────────────────

    public function simulatorTransactions(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $query = Transaction::with(['paymentMethod', 'merchant'])
            ->where('client_id', $client->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show actionable statuses
            $query->whereIn('status', [
                TransactionStatus::PENDING->value,
                TransactionStatus::PAID->value,
            ]);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(20);

        $transactions->setCollection(
            $transactions->getCollection()->map(fn($t) => [
                'transaction_id' => $t->transaction_id,
                'merchant_ref'   => $t->merchant_ref,
                'status'         => $t->status->value,
                'payment_method' => $t->paymentMethod?->method_code,
                'amount'         => (float) $t->gross_amount,
                'payment_data'   => $this->resolvePaymentData($t),
                'actions'        => $this->availableActions($t),
                'expired_at'     => $t->expired_at?->toDateTimeString(),
                'created_at'     => $t->created_at?->toDateTimeString(),
            ])
        );

        return $this->pagination($transactions, 'Simulator transactions retrieved');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/webhooks/simulator/trigger
    // Trigger a payment event for a transaction — fires the real webhook
    // to the client's callback_url and optionally updates the status.
    //
    // Body:
    //   transaction_id  — required
    //   event           — payment.paid | payment.failed | payment.expired (default: payment.paid)
    //   update_status   — bool, default true — also update transaction status
    // ─────────────────────────────────────────────────────────────────────────

    public function simulatorTrigger(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'event'          => 'sometimes|string|in:payment.paid,payment.failed,payment.expired',
        ]);

        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $transaction = Transaction::with(['paymentGateway', 'paymentMethod', 'client'])
            ->where('transaction_id', $request->transaction_id)
            ->where('client_id', $client->id)
            ->first();

        if (!$transaction) {
            return $this->notFound('Transaction not found');
        }

        $event = $request->input('event', WebhookEvent::PAYMENT_PAID->value);

        if ($transaction->status->isTerminal()) {
            return $this->error(
                ResponseCode::VALIDATION_ERROR,
                "Transaction is already in terminal status [{$transaction->status->value}] and cannot be triggered."
            );
        }

        if ($transaction->status !== TransactionStatus::PENDING) {
            return $this->error(
                ResponseCode::VALIDATION_ERROR,
                "Only pending transactions can be triggered. Current status: [{$transaction->status->value}]."
            );
        }

        try {
            $result = $this->simulatorService->trigger($transaction, $event);

            Log::info('[Simulator API] Webhook cycle triggered', [
                'transaction_id' => $transaction->transaction_id,
                'event'          => $event,
                'client_id'      => $client->id,
            ]);

            return $this->success([
                'transaction_id' => $result['transaction']->transaction_id,
                'status'         => $result['transaction']->status->value,
                'webhooks'       => [
                    'inbound'  => $result['inbound'],
                    'outbound' => $result['outbound'],
                ],
            ], 'Full webhook cycle simulated: inbound (PG → Platform) + outbound (Platform → Client).');
        } catch (\Throwable $e) {
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers (shared with simulator endpoints)
    // ─────────────────────────────────────────────────────────────────────────

    private function resolvePaymentData(Transaction $t): array
    {
        return match ($t->paymentMethod?->method_type?->value) {
            'virtual_account' => ['va_number' => $t->pg_va_number, 'bank' => $t->paymentMethod?->method_name],
            'qris'            => ['qr_string' => $t->pg_qr_string, 'qr_url' => $t->pg_checkout_url],
            'e_wallet',
            'paylater'        => ['checkout_url' => $t->pg_checkout_url, 'deeplink_url' => $t->pg_deeplink_url],
            default           => [],
        };
    }

    private function availableActions(Transaction $t): array
    {
        return match ($t->status) {
            TransactionStatus::PENDING => ['pay', 'fail', 'expire'],
            TransactionStatus::PAID    => $t->canBeRefunded() ? ['refund'] : [],
            default                    => [],
        };
    }
}
