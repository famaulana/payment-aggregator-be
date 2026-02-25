<?php

namespace App\Services\Payment;

use App\Enums\WebhookDirection;
use App\Jobs\ProcessInboundWebhook;
use App\Models\PaymentWebhookLog;
use App\Models\Transaction;
use App\Services\Gateway\GatewayFactory;
use Illuminate\Support\Facades\Log;

/**
 * SimulatorService
 *
 * Simulates the complete two-leg webhook cycle used in payment testing:
 *
 *  Leg 1 — INBOUND  (PG → Payment Platform)
 *    Build a properly-signed dummy payload in Bayarind's webhook format,
 *    validate it through the real gateway parser, and log it as an
 *    inbound PaymentWebhookLog entry.
 *
 *  Leg 2 — OUTBOUND (Payment Platform → Client)
 *    Call processStatusUpdate() which updates the transaction and
 *    dispatches DispatchOutboundWebhook to the client's callback_url.
 */
class SimulatorService
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Trigger a full webhook cycle for a transaction.
     *
     * @param  Transaction $transaction  Must have paymentGateway + client loaded.
     * @param  string      $event        WebhookEvent value: payment.paid | .failed | .expired
     * @return array       Debug info for both webhook legs + updated transaction.
     */
    public function trigger(Transaction $transaction, string $event): array
    {
        if (!$transaction->relationLoaded('paymentGateway')) {
            $transaction->load(['paymentGateway', 'client']);
        }

        $gateway = $transaction->paymentGateway;

        if (!$gateway) {
            throw new \RuntimeException('Transaction has no payment gateway associated.');
        }

        // ── Leg 1: Build + validate inbound webhook (PG → Platform) ──────────

        $inboundPayload = $this->buildInboundPayload($transaction, $event);
        [$signature, $signedWith] = $this->signPayload($inboundPayload, $gateway->getWebhookSecret());

        $inboundHeaders = [
            'content-type'         => ['application/json'],
            'x-bayarind-signature' => [$signature],
            'x-simulator'          => ['true'],
        ];

        // Log inbound (mirrors what WebhookController::inbound() does)
        $inboundLog = PaymentWebhookLog::create([
            'direction'      => WebhookDirection::INBOUND,
            'gateway_code'   => $gateway->pg_code,
            'event_type'     => $event,
            'transaction_id' => $transaction->id,
            'payload'        => $inboundPayload,
            'attempt_count'  => 1,
            'is_verified'    => false,
        ]);

        // Parse through the real gateway (signature validation + status mapping)
        $gatewayInstance = GatewayFactory::make($gateway);
        $webhookResult   = $gatewayInstance->parseWebhook($inboundPayload, $inboundHeaders);

        $inboundLog->update([
            'is_verified'  => $webhookResult->isValid,
            'processed_at' => now(),
        ]);

        Log::info('[Simulator] Inbound webhook processed', [
            'transaction_id' => $transaction->transaction_id,
            'event'          => $event,
            'is_valid'       => $webhookResult->isValid,
            'pg_status'      => $inboundPayload['status'],
        ]);

        // ── Leg 2: Status update + outbound webhook (Platform → Client) ──────

        $autoSuccess = config('app.simulator_auto_success', true);

        if ($autoSuccess) {
            // Synchronous: update status + dispatch outbound webhook immediately.
            // No queue worker needed — ideal for quick sandbox testing.
            $this->paymentService->processStatusUpdate($transaction, $webhookResult->status);
        } else {
            // Async: dispatch ProcessInboundWebhook to queue.
            // Queue worker must be running to process the webhook and update the transaction.
            ProcessInboundWebhook::dispatch($inboundLog->id, $gateway->pg_code, $inboundPayload, $inboundHeaders)
                ->onQueue('webhooks');
        }

        $transaction = $transaction->fresh()->load(['paymentMethod', 'paymentGateway', 'client']);

        $callbackUrl = $transaction->callback_url
            ?? $transaction->client?->settlement_config['callback_url']
            ?? null;

        Log::info('[Simulator] Leg 2 dispatched', [
            'transaction_id' => $transaction->transaction_id,
            'mode'           => $autoSuccess ? 'sync' : 'async (queued)',
            'callback_url'   => $callbackUrl,
        ]);

        // ── Build debug response ──────────────────────────────────────────────

        return [
            'inbound'     => $this->inboundDebug(
                $gateway->pg_code,
                $gateway->pg_name,
                $inboundLog->id,
                $inboundPayload,
                $inboundHeaders,
                $webhookResult->isValid,
                $signedWith,
            ),
            'outbound'    => $this->outboundDebug($event, $callbackUrl, $autoSuccess),
            'transaction' => $transaction,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Payload builder — Bayarind inbound webhook format
    // ─────────────────────────────────────────────────────────────────────────

    private function buildInboundPayload(Transaction $transaction, string $event): array
    {
        $pgStatus = match ($event) {
            'payment.paid'     => 'paid',
            'payment.failed'   => 'failed',
            'payment.expired'  => 'expired',
            'payment.refunded' => 'refunded',
            default            => 'pending',
        };

        // Use pg_reference_id so ProcessInboundWebhook finds the transaction by pg_reference_id.
        // Falls back to transaction_id if no PG reference yet.
        $pgRef = $transaction->pg_reference_id ?? $transaction->transaction_id;

        return array_filter([
            'transaction_id' => $pgRef,           // Bayarind's reference for our transaction
            'merchant_ref'   => $transaction->merchant_ref,
            'status'         => $pgStatus,
            'amount'         => (float) $transaction->gross_amount,
            'currency'       => $transaction->currency ?? 'IDR',
            'paid_at'        => $pgStatus === 'paid' ? now()->toIso8601String() : null,
            'va_number'      => $transaction->pg_va_number,
            'payment_method' => $transaction->paymentMethod?->method_code,
            'simulator'      => true,
            'timestamp'      => now()->toIso8601String(),
        ], fn($v) => $v !== null);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Signing — HMAC-SHA256 matching BayarindGateway::validateWebhookSignature()
    // ─────────────────────────────────────────────────────────────────────────

    private function signPayload(array $payload, ?string $secret): array
    {
        if (!$secret) {
            return ['no-secret-configured', 'none (gateway has no webhook_secret)'];
        }

        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);

        return [$signature, 'HMAC-SHA256 with gateway.webhook_secret'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Debug response helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function inboundDebug(
        string $pgCode,
        string $pgName,
        int    $logId,
        array  $payload,
        array  $headers,
        bool   $signatureValid,
        string $signedWith,
    ): array {
        return [
            'description'    => "{$pgName} → Payment Platform (simulated)",
            'endpoint'       => url("/api/v1/webhooks/inbound/{$pgCode}"),
            'log_id'         => $logId,
            'gateway'        => $pgCode,
            'payload'        => $payload,
            'headers'        => [
                'Content-Type'         => 'application/json',
                'X-Bayarind-Signature' => $headers['x-bayarind-signature'][0] ?? null,
                'X-Simulator'          => 'true',
            ],
            'signature_valid' => $signatureValid,
            'signed_with'     => $signedWith,
        ];
    }

    private function outboundDebug(string $event, ?string $callbackUrl, bool $autoSuccess = true): array
    {
        if ($autoSuccess) {
            $note = $callbackUrl
                ? 'Status updated synchronously. DispatchOutboundWebhook queued to webhooks queue.'
                : 'Status updated synchronously. No callback_url — outbound skipped.';
        } else {
            $note = 'ProcessInboundWebhook dispatched to webhooks queue. Run queue worker to process.';
        }

        return [
            'description'  => 'Payment Platform → Client callback',
            'mode'         => $autoSuccess ? 'sync' : 'async (queued)',
            'event'        => $event,
            'callback_url' => $callbackUrl ?? null,
            'dispatched'   => true,
            'note'         => $note,
        ];
    }
}
