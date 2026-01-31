<?php

namespace App\Services\Shared;

use App\Enums\AuditActionType;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditTrailService
{
    public function log(
        string $actionType,
        Model|string|null $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null
    ): AuditTrail {
        $request = request();
        $user = Auth::user();

        $auditableType = null;
        $auditableId = null;

        if ($auditable instanceof Model) {
            $auditableType = get_class($auditable);
            $auditableId = $auditable->id;
        } elseif (is_string($auditable)) {
            $auditableType = $auditable;
        }

        $changesSummary = $this->generateChangesSummary($oldValues, $newValues);

        return AuditTrail::create([
            'user_id' => $user?->id,
            'user_role' => $user?->role_name ?? $this->getCurrentUserRole(),
            'action_type' => $actionType,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
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
        $request = request();
        
        return AuditTrail::create([
            'user_id' => $user->id,
            'user_role' => $user->role_name ?? $user->roles->first()?->name,
            'action_type' => AuditActionType::LOGIN->value,
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'new_values' => [
                'email' => $user->email,
                'login_at' => now()->toDateTimeString(),
            ],
            'changes_summary' => 'User logged in successfully',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'endpoint' => $request?->fullUrl(),
            'http_method' => $request?->method(),
            'notes' => "User {$user->email} logged in successfully",
        ]);
    }

    public function logLoginFailed(string $email, ?string $reason = null): AuditTrail
    {
        return $this->log(
            actionType: AuditActionType::LOGIN_FAILED->value,
            auditable: 'user',
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
        $request = request();
        
        return AuditTrail::create([
            'user_id' => $user->id,
            'user_role' => $user->role_name ?? $user->roles->first()?->name,
            'action_type' => AuditActionType::LOGOUT->value,
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'changes_summary' => 'User logged out',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'endpoint' => $request?->fullUrl(),
            'http_method' => $request?->method(),
            'notes' => "User {$user->email} logged out",
        ]);
    }

    public function logApiKeyCreate(int $apiKeyId, array $keyData): AuditTrail
    {
        return AuditTrail::create([
            'user_id' => Auth::id(),
            'user_role' => Auth::user()?->role_name,
            'action_type' => AuditActionType::API_KEY_CREATE->value,
            'auditable_type' => 'api_key',
            'auditable_id' => $apiKeyId,
            'new_values' => $keyData,
            'changes_summary' => "API Key created: {$keyData['key_name']}",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'endpoint' => request()?->fullUrl(),
            'http_method' => request()?->method(),
            'notes' => "API Key created: {$keyData['key_name']}",
        ]);
    }

    public function logApiKeyRevoke(int $apiKeyId, ?string $reason = null): AuditTrail
    {
        return AuditTrail::create([
            'user_id' => Auth::id(),
            'user_role' => Auth::user()?->role_name,
            'action_type' => AuditActionType::API_KEY_REVOKE->value,
            'auditable_type' => 'api_key',
            'auditable_id' => $apiKeyId,
            'new_values' => [
                'revoked_at' => now()->toDateTimeString(),
                'reason' => $reason,
            ],
            'changes_summary' => "API Key revoked: {$apiKeyId}",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'endpoint' => request()?->fullUrl(),
            'http_method' => request()?->method(),
            'notes' => "API Key revoked: {$apiKeyId}",
        ]);
    }

    public function logApiKeyLogin(int $apiKeyId, string $clientCode): AuditTrail
    {
        return AuditTrail::create([
            'user_id' => null,
            'user_role' => 'api_key',
            'action_type' => AuditActionType::LOGIN->value,
            'auditable_type' => 'api_key',
            'auditable_id' => $apiKeyId,
            'new_values' => [
                'client_code' => $clientCode,
                'login_at' => now()->toDateTimeString(),
            ],
            'changes_summary' => "API Key login for client: {$clientCode}",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'endpoint' => request()?->fullUrl(),
            'http_method' => request()?->method(),
            'notes' => "API Key login for client: {$clientCode}",
        ]);
    }

    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditTrail::with(['user', 'auditable'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['user_role'])) {
            $query->where('user_role', $filters['user_role']);
        }

        if (isset($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (isset($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (isset($filters['auditable_id'])) {
            $query->where('auditable_id', $filters['auditable_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    private function getCurrentUserRole(): ?string
    {
        $user = Auth::user();
        return $user?->roles->first()?->name;
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
