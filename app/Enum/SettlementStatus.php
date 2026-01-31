<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case IN_SETTLEMENT = 'in_settlement';
    case SETTLED = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_SETTLEMENT => 'In Settlement',
            self::SETTLED => 'Settled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
