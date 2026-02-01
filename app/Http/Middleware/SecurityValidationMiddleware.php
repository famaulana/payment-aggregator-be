<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityValidationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for login endpoints
        if ($request->is('*/login')) {
            return $next($request);
        }

        // Validate content type for API requests
        if ($request->is('api/*')) {
            $contentType = $request->header('Content-Type');
            if ($contentType && !Str::startsWith($contentType, 'application/json')) {
                return response()->json([
                    'response_code' => \App\Enums\ResponseCode::INVALID_FORMAT->value,
                    'response_message' => __('messages.invalid_format'),
                ], \App\Enums\ResponseCode::INVALID_FORMAT->getHttpStatusCode());
            }
        }

        // Limit request size (e.g., 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($request->server('CONTENT_LENGTH') > $maxSize) {
            return response()->json([
                'response_code' => \App\Enums\ResponseCode::INVALID_INPUT->value,
                'response_message' => __('messages.invalid_input'),
            ], \App\Enums\ResponseCode::INVALID_INPUT->getHttpStatusCode());
        }

        // Sanitize input data to prevent XSS and SQL injection
        $this->sanitizeInput($request);

        // Validate for potential malicious patterns
        if ($this->hasMaliciousPattern($request)) {
            return response()->json([
                'response_code' => \App\Enums\ResponseCode::INVALID_INPUT->value,
                'response_message' => __('messages.invalid_input'),
            ], \App\Enums\ResponseCode::INVALID_INPUT->getHttpStatusCode());
        }

        return $next($request);
    }

    /**
     * Sanitize input data to prevent XSS and SQL injection
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array values
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize individual value
     */
    private function sanitizeValue($value): string
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove potentially dangerous characters/sequences
        $value = strip_tags($value); // Remove HTML tags
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); // Convert special chars
        
        // Remove potential SQL injection patterns
        $value = preg_replace('/(\'|")\s*(--|#|\/\*|\x00)/i', '', $value);
        
        return $value;
    }

    /**
     * Check for potentially malicious patterns
     */
    private function hasMaliciousPattern(Request $request): bool
    {
        $input = $request->all();
        $inputString = json_encode($input);

        // Common malicious patterns
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', // XSS script tags
            '/javascript:/i', // JavaScript protocol
            '/vbscript:/i', // VBScript protocol
            '/on\w+\s*=/i', // Event handlers
            '/(union\s+select|drop\s+\w+|create\s+\w+|delete\s+from|insert\s+into)/i', // SQL injection
            '/(exec\s*\(|xp_\w+|sp_\w+)/i', // Stored procedure execution
            '/(\.\.\/)+/', // Directory traversal
            '/etc\/passwd/i', // Common file inclusion targets
            '/boot\.ini/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                return true;
            }
        }

        return false;
    }
}