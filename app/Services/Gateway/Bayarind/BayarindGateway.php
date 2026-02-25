<?php

namespace App\Services\Gateway\Bayarind;

use App\DTOs\CreatePaymentDTO;
use App\DTOs\PaymentResultDTO;
use App\DTOs\RefundDTO;
use App\DTOs\RefundResultDTO;
use App\DTOs\WebhookResultDTO;
use App\Models\PaymentGateway;
use App\Services\Gateway\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class BayarindGateway implements PaymentGatewayInterface
{
    private ?string $webhookSecret;

    public function __construct(private PaymentGateway $gateway)
    {
        $this->webhookSecret = $gateway->getWebhookSecret();
    }

    public function getSupportedMethods(): array
    {
        return [
            'qris', 'va_bca', 'va_mandiri', 'va_bri', 'va_bni',
            'dana', 'ovo', 'shopeepay',
            'akulaku', 'kredivo',
        ];
    }

    public function createPayment(CreatePaymentDTO $dto): PaymentResultDTO
    {
        // Route to specific handler based on payment method
        if ($dto->paymentMethod === 'virtual_account') {
            return $this->createVirtualAccount($dto);
        }

        if ($dto->paymentMethod === 'qris') {
            return $this->createQris($dto);
        }

        // Mock for e_wallet and paylater
        return $this->createMockPayment($dto);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Virtual Account (Mock)
    // ─────────────────────────────────────────────────────────────────────────

    private function createVirtualAccount(CreatePaymentDTO $dto): PaymentResultDTO
    {
        // payment_channel format: va_bca, va_mandiri, va_bri, va_bni
        $channel = strtolower($dto->paymentChannel ?? 'va_bca');

        // Extract bank name from channel (remove 'va_' prefix)
        $bankCode = str_replace('va_', '', $channel);

        // Map bank code to VA prefix
        $vaPrefix = match($bankCode) {
            'bca' => '80001',
            'mandiri' => '89001',
            'bri' => '77001',
            'bni' => '88001',
            default => '90001'
        };

        $vaNumber = $vaPrefix . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $mockRef = 'BYNRD-VA-' . strtoupper($bankCode) . '-' . strtoupper(uniqid());

        Log::info('[Bayarind][MOCK] createVirtualAccount', [
            'transaction_id' => $dto->transactionId,
            'payment_channel' => $channel,
            'bank_code' => $bankCode,
            'va_number' => $vaNumber,
            'amount' => $dto->amount,
        ]);

        return new PaymentResultDTO(
            success:       true,
            pgReferenceId: $mockRef,
            status:        'pending',
            vaNumber:      $vaNumber,
            qrString:      null,
            qrUrl:         null,
            checkoutUrl:   null,
            deeplinkUrl:   null,
            expiredAt:     now()->addHours(24)->toIso8601String(),
            rawResponse:   [
                'mock' => true,
                'payment_channel' => $channel,
                'bank_code' => strtoupper($bankCode),
                'va_number' => $vaNumber,
                'note' => 'Mock Bayarind VA payment'
            ],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QRIS (Mock)
    // ─────────────────────────────────────────────────────────────────────────

    private function createQris(CreatePaymentDTO $dto): PaymentResultDTO
    {
        $mockRef = 'BYNRD-QR-' . strtoupper(uniqid());
        $mockQrString = '00020101021226580016ID.CO.SANDBOX.BAYARIND.WWW0118936005200200000000053033605802ID5910Bayarind6007Jakarta6105101106304';

        Log::info('[Bayarind][MOCK] createQris', [
            'transaction_id' => $dto->transactionId,
            'amount' => $dto->amount,
        ]);

        return new PaymentResultDTO(
            success:       true,
            pgReferenceId: $mockRef,
            status:        'pending',
            vaNumber:      null,
            qrString:      $mockQrString,
            qrUrl:         'https://sandbox.bayarind.id/qr/' . $mockRef,
            checkoutUrl:   null,
            deeplinkUrl:   null,
            expiredAt:     now()->addMinutes(30)->toIso8601String(),
            rawResponse:   [
                'mock' => true,
                'qr_string' => $mockQrString,
                'note' => 'Mock Bayarind QRIS payment'
            ],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mock (E-Wallet, PayLater)
    // ─────────────────────────────────────────────────────────────────────────

    private function createMockPayment(CreatePaymentDTO $dto): PaymentResultDTO
    {
        $mockRef = 'BYNRD-MOCK-' . strtoupper(substr($dto->transactionId, -8));
        $mockCheckout = 'https://sandbox.bayarind.id/pay/' . $mockRef;

        return new PaymentResultDTO(
            success:       true,
            pgReferenceId: $mockRef,
            status:        'pending',
            vaNumber:      null,
            qrString:      null,
            qrUrl:         null,
            checkoutUrl:   $mockCheckout,
            deeplinkUrl:   null,
            expiredAt:     now()->addHours(24)->toIso8601String(),
            rawResponse:   ['mock' => true, 'ref' => $mockRef],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Status Check (Mock)
    // ─────────────────────────────────────────────────────────────────────────

    public function getPaymentStatus(string $pgReferenceId): PaymentResultDTO
    {
        Log::info('[Bayarind][MOCK] getPaymentStatus', ['pg_reference_id' => $pgReferenceId]);

        // MOCK: Always return pending for demo purposes
        return new PaymentResultDTO(
            success:       true,
            pgReferenceId: $pgReferenceId,
            status:        'pending',
            vaNumber:      null,
            qrString:      null,
            qrUrl:         null,
            checkoutUrl:   null,
            deeplinkUrl:   null,
            expiredAt:     null,
            rawResponse:   ['mock' => true, 'status' => 'pending'],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cancel (Mock)
    // ─────────────────────────────────────────────────────────────────────────

    public function cancelPayment(string $pgReferenceId): bool
    {
        Log::info('[Bayarind][MOCK] cancelPayment', ['pg_reference_id' => $pgReferenceId]);
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Refund (Mock)
    // ─────────────────────────────────────────────────────────────────────────

    public function refund(RefundDTO $dto): RefundResultDTO
    {
        $mockRefundId = 'BYNRD-REF-' . strtoupper(uniqid());

        Log::info('[Bayarind][MOCK] refund', [
            'pg_reference_id' => $dto->pgReferenceId,
            'amount' => $dto->amount,
            'refund_id' => $mockRefundId,
        ]);

        return new RefundResultDTO(
            success:    true,
            pgRefundId: $mockRefundId,
            status:     'pending',
            rawResponse: [
                'mock' => true,
                'refund_id' => $mockRefundId,
                'note' => 'Mock Bayarind refund'
            ],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Webhook
    // ─────────────────────────────────────────────────────────────────────────

    public function validateWebhookSignature(array $payload, array $headers): bool
    {
        $secret = $this->webhookSecret;
        if (!$secret) {
            return true; // no secret configured, skip
        }

        $receivedSig = $headers['x-bayarind-signature'] ?? $headers['X-Bayarind-Signature'] ?? null;
        if (is_array($receivedSig)) {
            $receivedSig = $receivedSig[0] ?? null;
        }
        if (!$receivedSig) {
            return false;
        }

        $expectedSig = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
        return hash_equals($expectedSig, $receivedSig);
    }

    public function parseWebhook(array $payload, array $headers): WebhookResultDTO
    {
        $isValid = $this->validateWebhookSignature($payload, $headers);
        $status  = $this->mapStatus($payload['status'] ?? 'pending');

        return new WebhookResultDTO(
            isValid:       $isValid,
            pgReferenceId: $payload['transaction_id'] ?? '',
            status:        $status,
            amount:        (float) ($payload['amount'] ?? 0),
            paidAt:        $payload['paid_at'] ?? null,
            rawPayload:    $payload,
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────

    private function mapStatus(string $pgStatus): string
    {
        return match (strtolower($pgStatus)) {
            'success', 'paid', 'settlement', 'capture' => 'paid',
            'pending', 'waiting_payment'                => 'pending',
            'failed', 'deny', 'cancel'                  => 'failed',
            'expire', 'expired'                         => 'expired',
            'refund', 'refunded'                        => 'refunded',
            default                                     => 'pending',
        };
    }
}
