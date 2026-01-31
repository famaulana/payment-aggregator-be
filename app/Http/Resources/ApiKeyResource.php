<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Determine if the current user can view full API key based on their role
        $currentUser = auth()->user();
        $canViewFullKey = false;

        if ($currentUser) {
            // Allow system owners and their staff to see full keys
            $canViewFullKey = $currentUser->isSystemOwner();

            // Also allow client users to see their own keys
            if (!$canViewFullKey && $currentUser->isClientUser()) {
                $canViewFullKey = $this->client_id == $currentUser->getClientId();
            }
        }

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'key_name' => $this->key_name,
            'api_key' => $canViewFullKey ? $this->api_key : $this->maskApiKey($this->api_key),
            'environment' => $this->environment,
            'status' => $this->status,
            'ip_whitelist' => $this->ip_whitelist,
            'rate_limit_per_minute' => $this->rate_limit_per_minute,
            'rate_limit_per_hour' => $this->rate_limit_per_hour,
            'last_used_at' => $this->last_used_at,
            'total_requests' => $this->total_requests,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'revoked_by' => $this->revoked_by,
            'revoked_at' => $this->revoked_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships (without sensitive data)
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'client_name' => $this->client->client_name,
                    'client_code' => $this->client->client_code,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'username' => $this->creator->username,
                    'full_name' => $this->creator->full_name,
                ];
            }),

            'revoker' => $this->whenLoaded('revoker', function () {
                return [
                    'id' => $this->revoker->id ?? null,
                    'username' => $this->revoker->username ?? null,
                    'full_name' => $this->revoker->full_name ?? null,
                ];
            }),

            'revoked_by_info' => $this->whenLoaded('revokedBy', function () {
                return [
                    'id' => $this->revokedBy->id ?? null,
                    'username' => $this->revokedBy->username ?? null,
                    'full_name' => $this->revokedBy->full_name ?? null,
                ];
            }),
        ];
    }

    /**
     * Mask the API key for security
     */
    private function maskApiKey(?string $apiKey): ?string
    {
        if (!$apiKey) {
            return null;
        }

        // Show only first 6 and last 4 characters
        $length = strlen($apiKey);
        if ($length <= 10) {
            return str_repeat('*', $length);
        }

        $start = substr($apiKey, 0, 6);
        $end = substr($apiKey, -4);
        $maskLength = $length - strlen($start) - strlen($end);

        return $start . str_repeat('*', $maskLength) . $end;
    }
}