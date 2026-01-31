<?php

namespace App\Services\Api;

use App\Enums\ApiKeyStatus;
use App\Models\ApiKey;
use App\Models\User;
use App\Services\Shared\AuditTrailService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;

class ApiAuthService
{
    private const API_SERVER_CLIENT = 'api_server';

    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function login(string $apiKey, string $apiSecret): array
    {
        $keyRecord = ApiKey::where('api_key', $apiKey)
            ->where('status', ApiKeyStatus::ACTIVE)
            ->with('client.users') // Load the client and its associated users
            ->first();

        if (!$keyRecord) {
            throw new AuthenticationException(__('auth.invalid_api_key'));
        }

        if (!Hash::check($apiSecret, $keyRecord->api_secret_hashed)) {
            throw new AuthenticationException(__('auth.invalid_api_secret'));
        }

        $this->validateApiKeyAccess($keyRecord);

        // Get the first user associated with the client
        $user = $keyRecord->client->users->first();

        if (!$user) {
            throw new AuthenticationException(__('auth.no_user_associated_with_client'));
        }

        $passportClient = $this->getPassportClient(self::API_SERVER_CLIENT);
        $password = $this->generateApiUserPassword($user);

        $tokenData = $this->issuePasswordGrantToken($user, $passportClient, $password);

        $this->updateLastUsedApiKey($keyRecord);
        $this->auditService->logApiKeyLogin($keyRecord->id, $keyRecord->client->client_code);

        return [
            'client' => [
                'id' => $keyRecord->client->id,
                'name' => $keyRecord->client->client_name,
                'code' => $keyRecord->client->client_code,
            ],
            'token' => $tokenData,
        ];
    }

    public function logout(): bool
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthenticationException(__('auth.unauthenticated'));
        }

        $token = $user->token();

        if (!$token) {
            throw new AuthenticationException(__('auth.token_not_found'));
        }

        $token->revoke();

        $this->auditService->logLogout($user);

        return true;
    }

    public function me(): array
    {
        $user = auth()->user();

        // Check if the authenticated user is associated with a client
        if ($user->entity_type !== \App\Models\Client::class) {
            throw new AuthenticationException(__('auth.client_not_found'));
        }

        $client = $user->entity;

        if (!$client || !($client instanceof \App\Models\Client)) {
            throw new AuthenticationException(__('auth.client_not_found'));
        }

        return [
            'client' => [
                'id' => $client->id,
                'code' => $client->client_code,
                'name' => $client->client_name,
                'business_type' => $client->business_type,
                'kyb_status' => $client->kyb_status->value,
            ],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
            ],
        ];
    }

    private function validateApiKeyAccess(ApiKey $keyRecord): void
    {
        if ($keyRecord->status !== ApiKeyStatus::ACTIVE) {
            throw new AuthenticationException(__('auth.api_key_inactive'));
        }

        if ($keyRecord->ip_whitelist) {
            $requestIp = request()->ip();
            $whitelist = explode(',', $keyRecord->ip_whitelist);

            if (!in_array($requestIp, $whitelist)) {
                throw new AuthenticationException(__('auth.ip_not_allowed'));
            }
        }
    }


    private function generateApiUserPassword(User $user): string
    {
        return $user->password;
    }

    private function updateLastUsedApiKey(ApiKey $keyRecord): void
    {
        $keyRecord->update([
            'last_used_at' => now(),
            'total_requests' => $keyRecord->total_requests + 1,
        ]);
    }

    private function getPassportClient(string $clientType): \Laravel\Passport\Client
    {
        $clientName = $clientType === 'dashboard'
            ? 'Dashboard Password Grant'
            : 'API Server Password Grant';

        $client = \Laravel\Passport\Client::where('name', $clientName)->first();

        if (!$client) {
            throw new \RuntimeException("OAuth client '{$clientName}' not found");
        }

        $plainSecret = $clientType === 'dashboard'
            ? config('passport.dashboard_client.secret')
            : config('passport.api_server_client.secret');

        if (!$plainSecret) {
            throw new \RuntimeException("OAuth client '{$clientName}' secret not configured in config/passport.php");
        }

        $client->secret = $plainSecret;

        return $client;
    }

    private function issuePasswordGrantToken(User $user, \Laravel\Passport\Client $client, string $password): array
    {
        $expiresIn = (int) config('passport.access_token_ttl', 60) * 60;
        $expiresAt = now()->addSeconds($expiresIn);

        $tokenName = 'Password Grant Token';
        $token = $user->createToken($tokenName, [], $expiresAt);

        $accessToken = $token->accessToken;

        $refreshToken = null;
        $grantTypes = is_string($client->grant_types) ? json_decode($client->grant_types, true) : $client->grant_types;

        // Note: API server client typically doesn't have refresh tokens based on the seeder
        if (in_array('refresh_token', $grantTypes ?? [])) {
            $refreshToken = $this->createRefreshToken($token->token->id, $client);
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt->toDateTimeString(),
        ];
    }

    private function createRefreshToken(string $accessTokenId, \Laravel\Passport\Client $client): ?string
    {
        $refreshToken = str()->random(80);

        DB::table('oauth_refresh_tokens')->insert([
            'id' => $refreshToken,
            'access_token_id' => $accessTokenId,
            'revoked' => false,
            'expires_at' => now()->addDays((int) config('passport.refresh_token_ttl', 30)),
        ]);

        return $refreshToken;
    }
}
