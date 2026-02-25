<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case QRIS = 'qris';
    case VIRTUAL_ACCOUNT = 'virtual_account';
    case E_WALLET = 'e_wallet';
    case PAYLATER = 'paylater';

    public function label(): string
    {
        return match ($this) {
            self::QRIS => 'QRIS',
            self::VIRTUAL_ACCOUNT => 'Virtual Account',
            self::E_WALLET => 'E-Wallet',
            self::PAYLATER => 'PayLater',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
