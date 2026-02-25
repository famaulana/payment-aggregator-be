<?php

namespace App\Enums;

enum WebhookDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::INBOUND => 'Inbound (from PG)',
            self::OUTBOUND => 'Outbound (to client)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
