<?php

namespace App\Enums;

enum FloatingFundMovementType: string
{
    case ALLOCATION = 'allocation';
    case USAGE = 'usage';
    case REPAYMENT = 'repayment';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::ALLOCATION => 'Allocation',
            self::USAGE => 'Usage',
            self::REPAYMENT => 'Repayment',
            self::ADJUSTMENT => 'Adjustment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
