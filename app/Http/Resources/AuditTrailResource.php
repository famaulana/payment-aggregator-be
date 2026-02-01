<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'action_type' => $this->action_type,
            'auditable_type' => $this->when($this->auditable_type, function () {
                // Return short class name instead of full namespace
                return class_basename($this->auditable_type);
            }),
            'auditable_id' => $this->auditable_id,
            'changes_summary' => $this->changes_summary,
            'notes' => $this->notes,
            'ip_address' => $this->ip_address,
            'endpoint' => $this->endpoint,
            'http_method' => $this->http_method,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // User info - only minimal info, no sensitive data
            'user' => $this->when($this->user_id || $this->user, function () {
                return [
                    'id' => $this->user?->id ?? $this->user_id,
                    'username' => $this->user?->username,
                    'role' => $this->user_role,
                ];
            }),

            // Old values - mask sensitive data if present
            'old_values' => $this->when($this->old_values, function () {
                return $this->filterSensitiveData($this->old_values);
            }),

            // New values - mask sensitive data if present
            'new_values' => $this->when($this->new_values, function () {
                return $this->filterSensitiveData($this->new_values);
            }),
        ];
    }

    /**
     * Filter sensitive data from values
     */
    private function filterSensitiveData($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $sensitiveKeys = [
            'api_key',
            'api_secret',
            'api_key_hashed',
            'api_secret_hashed',
            'password',
            'token',
            'access_token',
            'refresh_token',
            'secret',
        ];

        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys)) {
                // Mask sensitive values
                $filtered[$key] = $this->maskSensitiveValue($value);
            } elseif (is_array($value)) {
                // Recursively filter nested arrays
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Mask sensitive value
     */
    private function maskSensitiveValue($value): string
    {
        if (!is_string($value)) {
            return '******';
        }

        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', 8);
        }

        // Show first 3 and last 3 characters
        return substr($value, 0, 3) . str_repeat('*', $length - 6) . substr($value, -3);
    }
}
