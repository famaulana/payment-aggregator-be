<?php

namespace App\Enums;

enum ReconciliationMatchStatus: string
{
    case MATCHED = 'matched';
    case UNMATCHED = 'unmatched';
    case DISPUTED = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::MATCHED => 'Matched',
            self::UNMATCHED => 'Unmatched',
            self::DISPUTED => 'Disputed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
