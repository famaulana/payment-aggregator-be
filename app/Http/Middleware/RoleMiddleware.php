<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (Auth::guest()) {
            return response()->json([
                'response_code' => \App\Enums\ResponseCode::UNAUTHORIZED->value,
                'response_message' => __('messages.unauthorized')
            ], \App\Enums\ResponseCode::UNAUTHORIZED->getHttpStatusCode());
        }

        $user = Auth::user();

        // Parse roles by pipe separator
        $roles = explode('|', $role);

        // Check if user has any of the required roles
        foreach ($roles as $r) {
            if ($user->hasRole($r)) {
                return $next($request);
            }
        }

        return response()->json([
            'response_code' => \App\Enums\ResponseCode::FORBIDDEN->value,
            'response_message' => __('messages.forbidden')
        ], \App\Enums\ResponseCode::FORBIDDEN->getHttpStatusCode());
    }
}