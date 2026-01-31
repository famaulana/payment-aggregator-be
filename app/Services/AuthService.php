<?php

namespace App\Services;

use App\Enums\ApiKeyStatus;
use App\Models\ApiKey;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Token;
use Laravel\Passport\AccessToken;

class AuthService implements AuthServiceInterface
{
    private const DASHBOARD_CLIENT = 'dashboard';
    private const API_SERVER_CLIENT = 'api_server';

    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function login(string $email, string $password, string $clientType = self::DASHBOARD_CLIENT): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->auditService->logLoginFailed($email, 'Invalid credentials');
            throw new AuthenticationException(__('auth.failed'));
        }

        if ($user->status !== 'active') {
            $this->auditService->logLoginFailed($email, 'Account inactive');
            throw new AuthenticationException(__('auth.inactive'));
        }

        $passportClient = $this->getPassportClient($clientType);

        $tokenData = $this->issuePasswordGrantToken($user, $passportClient, $password);

        $this->updateLastLogin($user);

        $this->auditService->logLoginSuccess($user);

        return [
            'user' => $this->transformUser($user),
            'token' => $tokenData,
        ];
    }

    public function loginWithApiKey(string $apiKey, string $apiSecret): array
    {
        $keyRecord = ApiKey::where('api_key', $apiKey)
            ->where('status', 'active')
            ->with('client')
            ->first();

        if (!$keyRecord) {
            throw new AuthenticationException(__('auth.invalid_api_key'));
        }

        if (!Hash::check($apiSecret, $keyRecord->api_secret_hashed)) {
            throw new AuthenticationException(__('auth.invalid_api_secret'));
        }

        $this->validateApiKeyAccess($keyRecord);

        $user = $this->getOrCreateApiUser($keyRecord->client);

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

    public function refreshToken(string $refreshToken): array
    {
        $passportClient = $this->getPassportClient(self::DASHBOARD_CLIENT);

        $tokenData = $this->issueRefreshGrantToken($refreshToken, $passportClient);

        $user = auth()->user();
        if ($user) {
            $this->auditService->logTokenRefresh($user);
        }

        return [
            'token' => $tokenData,
        ];
    }

    public function logout(AccessToken|Token $token): bool
    {
        $user = $token->tokenable ?? $token->user;

        // Revoke the token instead of deleting it
        $token->revoke();

        if ($user) {
            $this->auditService->logLogout($user);
        }

        return true;
    }

    public function logoutAllTokens(User $user): bool
    {
        $this->auditService->logLogout($user);

        // Revoke all user tokens
        foreach ($user->tokens as $token) {
            $token->revoke();
        }

        return true;
    }

    public function getUserTokens(User $user): \Illuminate\Support\Collection
    {
        return $user->tokens()
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'client' => $token->client->name,
                    'scopes' => $token->scopes,
                    'revoked' => $token->revoked,
                    'expires_at' => $token->expires_at ? $token->expires_at->toDateTimeString() : null,
                    'created_at' => $token->created_at->toDateTimeString(),
                ];
            });
    }

    private function getPassportClient(string $clientType): \Laravel\Passport\Client
    {
        $clientName = $clientType === self::DASHBOARD_CLIENT
            ? 'Dashboard Password Grant'
            : 'API Server Password Grant';

        $client = \Laravel\Passport\Client::where('name', $clientName)->first();

        if (!$client) {
            throw new \RuntimeException("OAuth client '{$clientName}' not found");
        }

        // Get plain text secret from config for password grant authentication
        // The database stores hashed secret, but OAuth requires plain text
        $plainSecret = $clientType === self::DASHBOARD_CLIENT
            ? config('passport.dashboard_client.secret')
            : config('passport.api_server_client.secret');

        if (!$plainSecret) {
            throw new \RuntimeException("OAuth client '{$clientName}' secret not configured in config/passport.php");
        }

        // Override the hashed secret from database with plain text from config
        $client->secret = $plainSecret;

        return $client;
    }

    private function issuePasswordGrantToken(User $user, \Laravel\Passport\Client $client, string $password): array
    {
        // Use Passport's Personal Access Token
        $expiresIn = (int) config('passport.access_token_ttl', 60) * 60;
        $expiresAt = now()->addSeconds($expiresIn);

        // Create token using Passport
        $tokenName = 'Password Grant Token';
        $token = $user->createToken($tokenName, [], $expiresAt);

        $accessToken = $token->accessToken;

        // Create refresh token if client supports it
        $refreshToken = null;
        $grantTypes = is_string($client->grant_types) ? json_decode($client->grant_types, true) : $client->grant_types;
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

    private function issueRefreshGrantToken(string $refreshToken, \Laravel\Passport\Client $client): array
    {
        // Find the refresh token
        $refreshTokenModel = DB::table('oauth_refresh_tokens')
            ->where('id', $refreshToken)
            ->where('revoked', false)
            ->first();

        if (!$refreshTokenModel) {
            throw new AuthenticationException(__('auth.invalid_refresh_token'));
        }

        // Check if refresh token expired
        if ($refreshTokenModel->expires_at && now()->greaterThan($refreshTokenModel->expires_at)) {
            throw new AuthenticationException(__('auth.refresh_token_expired'));
        }

        // Get the old access token to find the user
        $oldAccessToken = DB::table('oauth_access_tokens')
            ->where('id', $refreshTokenModel->access_token_id)
            ->first();

        if (!$oldAccessToken) {
            throw new AuthenticationException(__('auth.invalid_refresh_token'));
        }

        $user = User::find($oldAccessToken->user_id);

        if (!$user) {
            throw new AuthenticationException(__('auth.user_not_found'));
        }

        // Revoke old tokens
        DB::table('oauth_access_tokens')
            ->where('id', $refreshTokenModel->access_token_id)
            ->update(['revoked' => true]);

        DB::table('oauth_refresh_tokens')
            ->where('id', $refreshToken)
            ->update(['revoked' => true]);

        // Generate new tokens
        $password = $user->password; // Not actually used, just for interface compatibility
        return $this->issuePasswordGrantToken($user, $client, $password);
    }

    private function validateApiKeyAccess(ApiKey $apiKey): void
    {
        if ($apiKey->status !== ApiKeyStatus::ACTIVE) {
            throw new AuthenticationException(__('auth.api_key_inactive'));
        }

        if ($apiKey->ip_whitelist) {
            $requestIp = request()->ip();
            $whitelist = is_array($apiKey->ip_whitelist) ? $apiKey->ip_whitelist : json_decode($apiKey->ip_whitelist, true);

            if (!in_array($requestIp, $whitelist) && !empty($whitelist)) {
                throw new AuthenticationException(__('auth.ip_not_allowed'));
            }
        }
    }

    private function getOrCreateApiUser(\App\Models\Client $client): User
    {
        $user = User::firstOrCreate(
            [
                'email' => "api_{$client->client_code}@{$client->id}.internal",
            ],
            [
                'username' => "api_{$client->client_code}",
                'full_name' => "API User - {$client->client_name}",
                'password' => Hash::make(str()->random(64)),
                'status' => 'active',
                'client_id' => $client->id,
            ]
        );

        if (!$user->hasRole('client')) {
            $user->assignSingleRole('client');
        }

        return $user;
    }

    private function generateApiUserPassword(User $user): string
    {
        return $user->password;
    }

    private function updateLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    private function updateLastUsedApiKey(ApiKey $apiKey): void
    {
        $apiKey->update([
            'last_used_at' => now(),
            'total_requests' => $apiKey->total_requests + 1,
        ]);
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'role' => $user->role_name,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'entity_type' => $this->getUserEntityType($user),
            'client_id' => $user->client_id,
            'head_office_id' => $user->head_office_id,
            'merchant_id' => $user->merchant_id,
        ];
    }

    private function getUserEntityType(User $user): ?string
    {
        if ($user->client_id && !$user->head_office_id && !$user->merchant_id) {
            return 'client';
        }
        if ($user->head_office_id && !$user->merchant_id) {
            return 'head_office';
        }
        if ($user->merchant_id) {
            return 'merchant';
        }

        return null;
    }
}
