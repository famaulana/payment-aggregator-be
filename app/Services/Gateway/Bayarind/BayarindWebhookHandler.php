<?php

namespace App\Services\Gateway\Bayarind;

use App\DTOs\WebhookResultDTO;

class BayarindWebhookHandler
{
    public function handle(array $payload, array $headers, string $webhookSecret): WebhookResultDTO
    {
        $isValid = $this->verify($payload, $headers, $webhookSecret);

        $status = match (strtolower($payload['status'] ?? '')) {
            'success', 'paid', 'settlement' => 'paid',
            'failed', 'deny', 'cancel'      => 'failed',
            'expire', 'expired'             => 'expired',
            'refund', 'refunded'            => 'refunded',
            default                         => 'pending',
        };

        return new WebhookResultDTO(
            isValid:       $isValid,
            pgReferenceId: $payload['transaction_id'] ?? '',
            status:        $status,
            amount:        (float) ($payload['amount'] ?? 0),
            paidAt:        $payload['paid_at'] ?? null,
            rawPayload:    $payload,
        );
    }

    private function verify(array $payload, array $headers, string $secret): bool
    {
        if (empty($secret)) {
            return true;
        }

        $receivedSig = $headers['x-bayarind-signature']
            ?? $headers['X-Bayarind-Signature']
            ?? null;

        if (!$receivedSig) {
            return false;
        }

        $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
        return hash_equals($expected, $receivedSig);
    }
}
