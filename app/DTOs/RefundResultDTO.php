<?php

namespace App\DTOs;

class RefundResultDTO
{
    public function __construct(
        public readonly bool    $success,
        public readonly ?string $pgRefundId,
        public readonly string  $status,
        public readonly ?array  $rawResponse,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function failed(string $message, ?array $rawResponse = null): self
    {
        return new self(
            success:      false,
            pgRefundId:   null,
            status:       'failed',
            rawResponse:  $rawResponse,
            errorMessage: $message,
        );
    }
}
