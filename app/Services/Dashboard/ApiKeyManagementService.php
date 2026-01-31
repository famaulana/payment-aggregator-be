<?php

namespace App\Services\Dashboard;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\User;
use App\Services\Shared\AuditTrailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyManagementService
{
    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function createApiKey(
        int $clientId,
        string $keyName,
        string $environment = 'dev',
        ?array $ipWhitelist = null,
        int $rateLimitPerMinute = 60,
        int $rateLimitPerHour = 1000,
        ?string $notes = null,
        ?int $createdBy = null
    ): array {
        $client = Client::findOrFail($clientId);

        DB::beginTransaction();
        try {
            $plainApiKey = $this->generateApiKey();
            $plainApiSecret = $this->generateApiSecret();

            $apiKeyRecord = ApiKey::create([
                'client_id' => $clientId,
                'key_name' => $keyName,
                'api_key' => $plainApiKey,
                'api_key_hashed' => Hash::make($plainApiKey),
                'api_secret_hashed' => Hash::make($plainApiSecret),
                'environment' => $environment,
                'status' => 'active',
                'ip_whitelist' => $ipWhitelist ? json_encode($ipWhitelist) : null,
                'rate_limit_per_minute' => $rateLimitPerMinute,
                'rate_limit_per_hour' => $rateLimitPerHour,
                'notes' => $notes,
                'created_by' => $createdBy ?? auth()->id(),
            ]);

            $this->auditService->logApiKeyCreate($apiKeyRecord->id, [
                'client_id' => $clientId,
                'key_name' => $keyName,
                'environment' => $environment,
                'rate_limit_per_minute' => $rateLimitPerMinute,
                'rate_limit_per_hour' => $rateLimitPerHour,
            ]);

            DB::commit();

            return [
                'api_key' => $apiKeyRecord,
                'credentials' => [
                    'api_key' => $plainApiKey,
                    'api_secret' => $plainApiSecret,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateApiKey(
        int $apiKeyId,
        ?string $keyName = null,
        ?array $ipWhitelist = null,
        ?int $rateLimitPerMinute = null,
        ?int $rateLimitPerHour = null,
        ?string $notes = null
    ): ApiKey {
        $apiKey = ApiKey::with('client')->findOrFail($apiKeyId);

        $oldValues = [
            'key_name' => $apiKey->key_name,
            'ip_whitelist' => $apiKey->ip_whitelist,
            'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
            'rate_limit_per_hour' => $apiKey->rate_limit_per_hour,
            'notes' => $apiKey->notes,
        ];

        if ($keyName) $apiKey->key_name = $keyName;
        if ($ipWhitelist !== null) $apiKey->ip_whitelist = json_encode($ipWhitelist);
        if ($rateLimitPerMinute !== null) $apiKey->rate_limit_per_minute = $rateLimitPerMinute;
        if ($rateLimitPerHour !== null) $apiKey->rate_limit_per_hour = $rateLimitPerHour;
        if ($notes !== null) $apiKey->notes = $notes;

        $apiKey->save();

        $newValues = [
            'key_name' => $apiKey->key_name,
            'ip_whitelist' => $apiKey->ip_whitelist,
            'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
            'rate_limit_per_hour' => $apiKey->rate_limit_per_hour,
            'notes' => $apiKey->notes,
        ];

        app(AuditTrailService::class)->log(
            actionType: AuditTrailService::ACTION_API_KEY_UPDATE,
            auditable: $apiKey,
            oldValues: $oldValues,
            newValues: $newValues,
            notes: "API Key updated: {$apiKey->key_name}"
        );

        return $apiKey->fresh();
    }

    public function revokeApiKey(int $apiKeyId, ?string $reason = null): ApiKey
    {
        $apiKey = ApiKey::findOrFail($apiKeyId);

        $apiKey->update([
            'status' => 'revoked',
            'revoked_by' => auth()->id(),
            'revoked_at' => now(),
        ]);

        $this->auditService->logApiKeyRevoke($apiKeyId, $reason);

        return $apiKey->fresh();
    }

    public function regenerateApiSecret(int $apiKeyId): array
    {
        $apiKey = ApiKey::findOrFail($apiKeyId);

        $plainApiSecret = $this->generateApiSecret();

        $apiKey->update([
            'api_secret_hashed' => Hash::make($plainApiSecret),
        ]);

        app(\App\Services\AuditTrailService::class)->log(
            actionType: 'api_secret_regenerate',
            auditable: $apiKey,
            newValues: ['regenerated_at' => now()->toDateTimeString()],
            notes: "API Secret regenerated for: {$apiKey->key_name}"
        );

        return [
            'api_key' => $apiKey,
            'api_secret' => $plainApiSecret,
        ];
    }

    public function getApiKeys(array $filters = [], int $perPage = 20)
    {
        $query = ApiKey::with(['client', 'creator', 'revokedBy'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['environment'])) {
            $query->where('environment', $filters['environment']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('key_name', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('client_name', 'like', "%{$search}%")
                            ->orWhere('client_code', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function getApiKeyById(int $apiKeyId): ApiKey
    {
        return ApiKey::with(['client', 'creator', 'revokedBy'])
            ->findOrFail($apiKeyId);
    }

    public function getClientApiKeys(int $clientId, int $perPage = 20)
    {
        return ApiKey::with(['creator', 'revokedBy'])
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function toggleApiKeyStatus(int $apiKeyId): ApiKey
    {
        $apiKey = ApiKey::findOrFail($apiKeyId);

        $oldStatus = $apiKey->status;
        $newStatus = $oldStatus === 'active' ? 'inactive' : 'active';

        $apiKey->update(['status' => $newStatus]);

        app(\App\Services\Shared\AuditTrailService::class)->log(
            actionType: 'api_key_status_toggle',
            auditable: $apiKey,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
            notes: "API Key status changed to: {$newStatus}"
        );

        return $apiKey->fresh();
    }

    private function generateApiKey(): string
    {
        return 'pk_' . $this->generateRandomString(32);
    }

    private function generateApiSecret(): string
    {
        return 'sk_' . $this->generateRandomString(48);
    }

    private function generateRandomString(int $length): string
    {
        return strtoupper(Str::random($length));
    }
}
