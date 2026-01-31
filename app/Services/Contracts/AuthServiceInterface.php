<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Token;

interface AuthServiceInterface
{
    public function login(string $email, string $password, string $clientType): array;
    public function loginWithApiKey(string $apiKey, string $apiSecret): array;
    public function refreshToken(string $refreshToken): array;
    public function logout(Token $token): bool;
    public function logoutAllTokens(User $user): bool;
    public function getUserTokens(User $user): \Illuminate\Support\Collection;
}
