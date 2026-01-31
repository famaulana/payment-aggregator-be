<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKeyIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('api_key')) {
            $apiKey = \App\Models\ApiKey::where('api_key', $request->api_key)
                ->where('status', \App\Enums\ApiKeyStatus::ACTIVE)
                ->first();

            if ($apiKey && $apiKey->ip_whitelist) {
                $requestIp = $request->ip();

                if (!$this->isAllowedIp($requestIp, $apiKey->ip_whitelist)) {
                    return response()->json([
                        'response_code' => '2001',
                        'response_message' => __('auth.ip_not_allowed'),
                    ], 401);
                }
            }
        }

        return $next($request);
    }

    private function isAllowedIp(string $ip, mixed $whitelist): bool
    {
        if ($this->isLocalEnvironment($ip)) {
            return true;
        }

        $allowedIps = is_array($whitelist) ? $whitelist : json_decode($whitelist, true);

        return in_array($ip, $allowedIps ?? []);
    }

    private function isLocalEnvironment(string $ip): bool
    {
        $localIps = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ip, $localIps)) {
            return true;
        }

        if (str_starts_with($ip, '192.168.')) {
            return true;
        }

        if (str_starts_with($ip, '10.')) {
            return true;
        }

        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)) {
            return true;
        }

        return false;
    }
}

