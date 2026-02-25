<?php

namespace App\DTOs;

class CreatePaymentDTO
{
    public function __construct(
        public readonly int    $clientId,
        public readonly int    $merchantId,
        public readonly string $transactionId,
        public readonly string $merchantRef,
        public readonly string $paymentMethod,
        public readonly ?string $paymentChannel,
        public readonly float  $amount,
        public readonly string $currency,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly ?string $customerPhone,
        public readonly ?string $expiredAt,
        public readonly ?array  $items,
        public readonly ?array  $metadata,
        public readonly ?string $callbackUrl,
        public readonly ?string $redirectUrl,
    ) {}

    public static function fromArray(array $data, int $clientId, int $merchantId, string $transactionId): self
    {
        return new self(
            clientId:       $clientId,
            merchantId:     $merchantId,
            transactionId:  $transactionId,
            merchantRef:    $data['merchant_ref'],
            paymentMethod:  $data['payment_method'],
            paymentChannel: $data['payment_channel'] ?? null,
            amount:         (float) $data['amount'],
            currency:       $data['currency'] ?? 'IDR',
            customerName:   $data['customer']['name'],
            customerEmail:  $data['customer']['email'],
            customerPhone:  $data['customer']['phone'] ?? null,
            expiredAt:      $data['expired_at'] ?? null,
            items:          $data['items'] ?? null,
            metadata:       $data['metadata'] ?? null,
            callbackUrl:    $data['callback_url'] ?? null,
            redirectUrl:    $data['redirect_url'] ?? null,
        );
    }
}
