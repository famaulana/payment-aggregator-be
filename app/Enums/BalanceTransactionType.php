<?php

namespace App\Enums;

enum BalanceTransactionType: string
{
    case SETTLEMENT = 'settlement';
    case ADJUSTMENT = 'adjustment';
    case CORRECTION = 'correction';
    case PAYMENT = 'payment';

    public function label(): string
    {
        return match ($this) {
            self::SETTLEMENT => 'Settlement',
            self::ADJUSTMENT => 'Adjustment',
            self::CORRECTION => 'Correction',
            self::PAYMENT => 'Payment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
