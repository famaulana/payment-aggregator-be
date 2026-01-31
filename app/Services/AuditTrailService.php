<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditTrailService
{
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_API_KEY_LOGIN = 'api_key_login';
    public const ACTION_API_KEY_LOGOUT = 'api_key_logout';
    public const ACTION_API_KEY_CREATE = 'api_key_create';
    public const ACTION_API_KEY_UPDATE = 'api_key_update';
    public const ACTION_API_KEY_DELETE = 'api_key_delete';
    public const ACTION_API_KEY_REVOKE = 'api_key_revoke';
    public const ACTION_TOKEN_REFRESH = 'token_refresh';
    public const ACTION_PASSWORD_CHANGE = 'password_change';
    public const ACTION_PROFILE_UPDATE = 'profile_update';

    public function log(
        string $actionType,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null
    ): AuditTrail {
        $request = request();

        $user = Auth::user();
        $userId = $user?->id;
        $userRole = $user?->role_name;
        $clientId = $user?->client_id;

        $changesSummary = $this->generateChangesSummary($oldValues, $newValues);

        return AuditTrail::create([
            'user_id' => $userId,
            'user_role' => $userRole,
            'client_id' => $clientId,
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes_summary' => $changesSummary,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'endpoint' => $request?->fullUrl(),
            'http_method' => $request?->method(),
            'notes' => $notes,
        ]);
    }

    public function logLoginSuccess(User $user): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_LOGIN,
            entityType: 'user',
            entityId: $user->id,
            newValues: [
                'email' => $user->email,
                'login_at' => now()->toDateTimeString(),
            ],
            notes: 'User logged in successfully'
        );
    }

    public function logLoginFailed(string $email, ?string $reason = null): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_LOGIN_FAILED,
            entityType: 'user',
            newValues: [
                'email' => $email,
                'attempted_at' => now()->toDateTimeString(),
                'reason' => $reason,
            ],
            notes: "Failed login attempt for email: {$email}"
        );
    }

    public function logLogout(User $user): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_LOGOUT,
            entityType: 'user',
            entityId: $user->id,
            notes: 'User logged out'
        );
    }

    public function logApiKeyLogin(int $apiKeyId, string $clientCode): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_API_KEY_LOGIN,
            entityType: 'api_key',
            entityId: $apiKeyId,
            newValues: [
                'client_code' => $clientCode,
                'login_at' => now()->toDateTimeString(),
            ],
            notes: "API Key login for client: {$clientCode}"
        );
    }

    public function logApiKeyCreate(int $apiKeyId, array $keyData): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_API_KEY_CREATE,
            entityType: 'api_key',
            entityId: $apiKeyId,
            newValues: $keyData,
            notes: "API Key created: {$keyData['key_name']}"
        );
    }

    public function logApiKeyRevoke(int $apiKeyId, ?string $reason = null): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_API_KEY_REVOKE,
            entityType: 'api_key',
            entityId: $apiKeyId,
            newValues: [
                'revoked_at' => now()->toDateTimeString(),
                'reason' => $reason,
            ],
            notes: "API Key revoked: {$apiKeyId}"
        );
    }

    public function logTokenRefresh(User $user): AuditTrail
    {
        return $this->log(
            actionType: self::ACTION_TOKEN_REFRESH,
            entityType: 'user',
            entityId: $user->id,
            newValues: [
                'refreshed_at' => now()->toDateTimeString(),
            ],
            notes: 'Token refreshed'
        );
    }

    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditTrail::with(['user', 'client'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    private function generateChangesSummary(?array $oldValues, ?array $newValues): ?string
    {
        if (!$oldValues || !$newValues) {
            return null;
        }

        $changes = [];
        foreach ($newValues as $key => $value) {
            if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                $changes[] = "{$key}: '{$oldValues[$key]}' -> '{$value}'";
            } elseif (!isset($oldValues[$key])) {
                $changes[] = "{$key}: set to '{$value}'";
            }
        }

        return empty($changes) ? null : implode(', ', $changes);
    }
}
