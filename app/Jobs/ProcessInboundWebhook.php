<?php

namespace App\Jobs;

use App\Models\PaymentWebhookLog;
use App\Models\Transaction;
use App\Services\Gateway\GatewayFactory;
use App\Services\Payment\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private int    $logId,
        private string $gatewayCode,
        private array  $payload,
        private array  $headers,
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        $log = PaymentWebhookLog::find($this->logId);

        try {
            $gatewayInstance = GatewayFactory::makeByCode($this->gatewayCode);

            $result = $gatewayInstance->parseWebhook($this->payload, $this->headers);

            $log?->update([
                'is_verified'  => $result->isValid,
                'processed_at' => now(),
            ]);

            if (!$result->isValid) {
                Log::warning('[ProcessInboundWebhook] Invalid signature', [
                    'gateway' => $this->gatewayCode,
                    'pg_ref'  => $result->pgReferenceId,
                ]);
                return;
            }

            $transaction = Transaction::where('pg_reference_id', $result->pgReferenceId)
                ->orWhere('transaction_id', $result->pgReferenceId)
                ->with(['paymentGateway', 'client'])
                ->first();

            if (!$transaction) {
                Log::warning('[ProcessInboundWebhook] Transaction not found', [
                    'pg_ref' => $result->pgReferenceId,
                ]);
                return;
            }

            $log?->update(['transaction_id' => $transaction->id]);

            $paymentService->processStatusUpdate($transaction, $result->status);

            Log::info('[ProcessInboundWebhook] Processed', [
                'transaction_id' => $transaction->transaction_id,
                'status'         => $result->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProcessInboundWebhook] Failed', [
                'error'   => $e->getMessage(),
                'log_id'  => $this->logId,
                'gateway' => $this->gatewayCode,
            ]);
            throw $e;
        }
    }
}
