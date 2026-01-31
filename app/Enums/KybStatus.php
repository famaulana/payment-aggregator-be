<?php

namespace App\Enums;

enum KybStatus: string
{
    case NOT_REQUIRED = 'not_required';
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'Not Required',
            self::PENDING => 'Pending',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
