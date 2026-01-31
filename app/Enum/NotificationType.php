<?php

namespace App\Enums;

enum NotificationType: string
{
    case SETTLEMENT_APPROVED = 'settlement_approved';
    case SETTLEMENT_REJECTED = 'settlement_rejected';
    case MISMATCH_FOUND = 'mismatch_found';
    case BALANCE_ADJUSTED = 'balance_adjusted';
    case PAYOUT_PROCESSED = 'payout_processed';

    public function label(): string
    {
        return match ($this) {
            self::SETTLEMENT_APPROVED => 'Settlement Approved',
            self::SETTLEMENT_REJECTED => 'Settlement Rejected',
            self::MISMATCH_FOUND => 'Mismatch Found',
            self::BALANCE_ADJUSTED => 'Balance Adjusted',
            self::PAYOUT_PROCESSED => 'Payout Processed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
