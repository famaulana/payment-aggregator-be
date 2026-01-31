<?php

namespace App\Enums;

enum ReconciliationMatchType: string
{
    case EXACT_MATCH = 'exact_match';
    case PARTIAL_MATCH = 'partial_match';
    case NO_MATCH = 'no_match';
    case DUPLICATE = 'duplicate';
    case MISSING = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::EXACT_MATCH => 'Exact Match',
            self::PARTIAL_MATCH => 'Partial Match',
            self::NO_MATCH => 'No Match',
            self::DUPLICATE => 'Duplicate',
            self::MISSING => 'Missing',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
