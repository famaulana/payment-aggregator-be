<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case QRIS = 'qris';
    case VIRTUAL_ACCOUNT = 'virtual_account';
    case E_WALLET = 'e_wallet';
    case CREDIT_CARD = 'credit_card';
    case PAYLATER = 'paylater';
    case TRANSFER_BANK = 'transfer_bank';

    public function label(): string
    {
        return match ($this) {
            self::QRIS => 'QRIS',
            self::VIRTUAL_ACCOUNT => 'Virtual Account',
            self::E_WALLET => 'E-Wallet',
            self::CREDIT_CARD => 'Credit Card',
            self::PAYLATER => 'PayLater',
            self::TRANSFER_BANK => 'Transfer Bank',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
