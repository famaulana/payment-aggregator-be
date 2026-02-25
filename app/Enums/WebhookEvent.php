<?php

namespace App\Enums;

enum WebhookEvent: string
{
    case PAYMENT_PENDING = 'payment.pending';
    case PAYMENT_PAID = 'payment.paid';
    case PAYMENT_FAILED = 'payment.failed';
    case PAYMENT_EXPIRED = 'payment.expired';
    case PAYMENT_REFUNDED = 'payment.refunded';
    case SETTLEMENT_PROCESSED = 'settlement.processed';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT_PENDING => 'Payment Pending',
            self::PAYMENT_PAID => 'Payment Paid',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::PAYMENT_EXPIRED => 'Payment Expired',
            self::PAYMENT_REFUNDED => 'Payment Refunded',
            self::SETTLEMENT_PROCESSED => 'Settlement Processed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
