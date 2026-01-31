<?php

namespace App\Enums;

enum ApiKeyStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case REVOKED = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::REVOKED => 'Revoked',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
