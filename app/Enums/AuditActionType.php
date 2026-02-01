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

    // Authentication actions
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case LOGIN_FAILED = 'login_failed';
    case TOKEN_REFRESH = 'token_refresh';
    case PASSWORD_CHANGE = 'password_change';
    case PROFILE_UPDATE = 'profile_update';

    // API Key actions
    case API_KEY_LOGIN = 'api_key_login';
    case API_KEY_LOGOUT = 'api_key_logout';
    case API_KEY_CREATE = 'api_key_create';
    case API_KEY_UPDATE = 'api_key_update';
    case API_KEY_DELETE = 'api_key_delete';
    case API_KEY_REVOKE = 'api_key_revoke';
    case API_KEY_STATUS_TOGGLE = 'api_key_status_toggle';
    case API_SECRET_REGENERATE = 'api_secret_regenerate';

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
            self::LOGIN => 'Login',
            self::LOGOUT => 'Logout',
            self::LOGIN_FAILED => 'Login Failed',
            self::TOKEN_REFRESH => 'Token Refresh',
            self::PASSWORD_CHANGE => 'Password Change',
            self::PROFILE_UPDATE => 'Profile Update',
            self::API_KEY_LOGIN => 'API Key Login',
            self::API_KEY_LOGOUT => 'API Key Logout',
            self::API_KEY_CREATE => 'API Key Create',
            self::API_KEY_UPDATE => 'API Key Update',
            self::API_KEY_DELETE => 'API Key Delete',
            self::API_KEY_REVOKE => 'API Key Revoke',
            self::API_KEY_STATUS_TOGGLE => 'API Key Status Toggle',
            self::API_SECRET_REGENERATE => 'API Secret Regenerate',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
