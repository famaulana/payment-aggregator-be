<?php

namespace App\Enums;

enum ResolutionType: string
{
    case ADJUSTMENT = 'adjustment';
    case WRITE_OFF = 'write_off';
    case FOLLOW_UP = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::ADJUSTMENT => 'Adjustment',
            self::WRITE_OFF => 'Write Off',
            self::FOLLOW_UP => 'Follow Up',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
