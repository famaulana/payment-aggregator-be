<?php

namespace App\Services\Payment;

use App\Enums\WebhookEvent;
use App\Jobs\DispatchOutboundWebhook;
use App\Models\Transaction;

class WebhookDispatcherService
{
    public function dispatch(Transaction $transaction, WebhookEvent $event): void
    {
        // Ensure client is loaded (Client = mitra/partner, owner of the API key)
        if (!$transaction->relationLoaded('client')) {
            $transaction->load('client');
        }

        $callbackUrl = $transaction->callback_url
            ?? ($transaction->client?->settlement_config['callback_url'] ?? null);

        if (!$callbackUrl) {
            return;
        }

        $payload = [
            'event'      => $event->value,
            'event_id'   => 'EVT-' . strtoupper(uniqid()),
            'created_at' => now()->toIso8601String(),
            'data'       => [
                'transaction_id' => $transaction->transaction_id,
                'merchant_ref'   => $transaction->merchant_ref,
                'status'         => $transaction->status->value,
                'amount'         => (float) $transaction->gross_amount,
                'paid_at'        => $transaction->paid_at?->toIso8601String(),
            ],
        ];

        // Compute HMAC signature for outbound webhook.
        // Uses app key as signing secret — client should verify using their shared secret
        // (configured via dashboard when setting up the callback URL).
        $signingSecret = $transaction->client?->settlement_config['webhook_secret']
            ?? config('app.key');

        $payload['signature'] = hash_hmac('sha256', json_encode($payload['data'], JSON_UNESCAPED_SLASHES), $signingSecret);

        DispatchOutboundWebhook::dispatch($transaction->id, $callbackUrl, $payload)
            ->onQueue('webhooks');
    }
}
