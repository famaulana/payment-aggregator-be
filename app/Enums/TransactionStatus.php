<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';
    case IN_SETTLEMENT = 'in_settlement';
    case SETTLED = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::EXPIRED => 'Expired',
            self::REFUNDED => 'Refunded',
            self::IN_SETTLEMENT => 'In Settlement',
            self::SETTLED => 'Settled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FAILED, self::EXPIRED, self::REFUNDED, self::SETTLED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
