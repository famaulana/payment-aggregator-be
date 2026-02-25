<?php

namespace App\DTOs;

class RefundDTO
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $pgReferenceId,
        public readonly float  $amount,
        public readonly string $reason,
        public readonly ?string $refId,
    ) {}
}
