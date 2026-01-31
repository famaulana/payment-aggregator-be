<?php

namespace App\Enums;

enum Environment: string
{
    case DEV = 'dev';
    case STAGING = 'staging';
    case PRODUCTION = 'production';

    public function label(): string
    {
        return match ($this) {
            self::DEV => 'Development',
            self::STAGING => 'Staging',
            self::PRODUCTION => 'Production',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
