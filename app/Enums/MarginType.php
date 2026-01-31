<?php

namespace App\Enums;

enum MarginType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';
    case MIXED = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage',
            self::FIXED => 'Fixed',
            self::MIXED => 'Mixed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
