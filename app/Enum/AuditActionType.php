<?php

namespace App\Enums;

enum AuditActionType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case OVERRIDE = 'override';
    case ADJUST = 'adjust';

    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
            self::APPROVE => 'Approve',
            self::REJECT => 'Reject',
            self::OVERRIDE => 'Override',
            self::ADJUST => 'Adjust',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
