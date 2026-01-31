<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Shared\AuditTrailService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;
use Laravel\Passport\AccessToken;

class DashboardAuthService
{
    private const DASHBOARD_CLIENT = 'dashboard';

    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function login(string $email, string $password): array
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

        $passportClient = $this->getPassportClient(self::DASHBOARD_CLIENT);
        $tokenData = $this->issuePasswordGrantToken($user, $passportClient, $password);

        $this->updateLastLogin($user);
        $this->auditService->logLoginSuccess($user);

        return [
            'user' => $this->transformUser($user),
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

        $token->revoke();

        if ($user) {
            $this->auditService->logLogout($user);
        }

        return true;
    }

    public function logoutAll(): bool
    {
        $user = auth()->user();

        if ($user) {
            $this->auditService->logLogout($user);

            foreach ($user->tokens as $token) {
                $token->revoke();
            }
        }

        return true;
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

        $plainSecret = $clientType === self::DASHBOARD_CLIENT
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
        $refreshTokenModel = DB::table('oauth_refresh_tokens')
            ->where('id', $refreshToken)
            ->where('revoked', false)
            ->first();

        if (!$refreshTokenModel) {
            throw new AuthenticationException(__('auth.invalid_refresh_token'));
        }

        if ($refreshTokenModel->expires_at && now()->greaterThan($refreshTokenModel->expires_at)) {
            throw new AuthenticationException(__('auth.refresh_token_expired'));
        }

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

        DB::table('oauth_access_tokens')
            ->where('id', $refreshTokenModel->access_token_id)
            ->update(['revoked' => true]);

        DB::table('oauth_refresh_tokens')
            ->where('id', $refreshToken)
            ->update(['revoked' => true]);

        $password = $user->password;
        return $this->issuePasswordGrantToken($user, $client, $password);
    }

    private function updateLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    private function transformUser(User $user): array
    {
        $entity = $user->entity;
        $entityType = $user->getEntityTypeLabel();

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'role' => $user->role_name,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'entity_type' => strtolower(str_replace(' ', '_', $entityType)),
            'status' => $user->status,
        ];

        if ($entity instanceof \App\Models\Client) {
            $data['client'] = [
                'id' => $entity->id,
                'code' => $entity->client_code,
                'name' => $entity->client_name,
            ];
        } elseif ($entity instanceof \App\Models\HeadOffice) {
            $data['head_office'] = [
                'id' => $entity->id,
                'code' => $entity->code,
                'name' => $entity->name,
            ];
            $data['client'] = [
                'id' => $entity->client->id,
                'code' => $entity->client->client_code,
                'name' => $entity->client->client_name,
            ];
        } elseif ($entity instanceof \App\Models\Merchant) {
            $data['merchant'] = [
                'id' => $entity->id,
                'code' => $entity->merchant_code,
                'name' => $entity->merchant_name,
            ];
            if ($entity->headOffice) {
                $data['head_office'] = [
                    'id' => $entity->headOffice->id,
                    'code' => $entity->headOffice->code,
                    'name' => $entity->headOffice->name,
                ];
            }
            $data['client'] = [
                'id' => $entity->client->id,
                'code' => $entity->client->client_code,
                'name' => $entity->client->client_name,
            ];
        }

        return $data;
    }
}
