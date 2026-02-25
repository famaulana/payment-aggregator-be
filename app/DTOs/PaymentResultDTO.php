<?php

namespace App\DTOs;

class PaymentResultDTO
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $pgReferenceId,
        public readonly string  $status,
        public readonly ?string $vaNumber,
        public readonly ?string $qrString,
        public readonly ?string $qrUrl,
        public readonly ?string $checkoutUrl,
        public readonly ?string $deeplinkUrl,
        public readonly ?string $expiredAt,
        public readonly ?array  $rawResponse,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function failed(string $message, ?array $rawResponse = null): self
    {
        return new self(
            success:      false,
            pgReferenceId: '',
            status:       'failed',
            vaNumber:     null,
            qrString:     null,
            qrUrl:        null,
            checkoutUrl:  null,
            deeplinkUrl:  null,
            expiredAt:    null,
            rawResponse:  $rawResponse,
            errorMessage: $message,
        );
    }
}
