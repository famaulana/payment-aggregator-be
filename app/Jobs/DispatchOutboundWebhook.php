<?php

namespace App\Jobs;

use App\Enums\WebhookDirection;
use App\Models\PaymentWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchOutboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    public function __construct(
        private ?int   $transactionId,
        private string $targetUrl,
        private array  $payload,
        private bool   $isTest = false,
    ) {}

    public function handle(): void
    {
        $log = PaymentWebhookLog::create([
            'direction'      => WebhookDirection::OUTBOUND,
            'event_type'     => $this->payload['event'] ?? null,
            'transaction_id' => $this->transactionId,
            'target_url'     => $this->targetUrl,
            'payload'        => $this->payload,
            'attempt_count'  => $this->attempts(),
            'is_verified'    => true,
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type'   => 'application/json',
                'X-Webhook-Test' => $this->isTest ? 'true' : 'false',
            ])
            ->timeout(15)
            ->post($this->targetUrl, $this->payload);

            $log->update([
                'response_status' => $response->status(),
                'response_body'   => $response->json(),
                'processed_at'    => now(),
            ]);

            if (!$response->successful()) {
                Log::warning('[DispatchOutboundWebhook] Non-2xx response', [
                    'url'    => $this->targetUrl,
                    'status' => $response->status(),
                ]);
                $this->release(10);
            }
        } catch (\Throwable $e) {
            Log::error('[DispatchOutboundWebhook] Failed', [
                'url'   => $this->targetUrl,
                'error' => $e->getMessage(),
            ]);
            $log->update(['processed_at' => now()]);
            throw $e;
        }
    }
}
