<?php

namespace App\DTOs;

class WebhookResultDTO
{
    public function __construct(
        public readonly bool    $isValid,
        public readonly string  $pgReferenceId,
        public readonly string  $status,
        public readonly float   $amount,
        public readonly ?string $paidAt,
        public readonly ?array  $rawPayload,
    ) {}
}
