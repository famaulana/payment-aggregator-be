<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateApiKeyRequest;
use App\Http\Requests\UpdateApiKeyRequest;
use App\Services\Dashboard\ApiKeyManagementService;
use App\Enums\ResponseCode;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiKeyController extends Controller
{
    public function __construct(
        private ApiKeyManagementService $apiKeyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'client_id' => $request->client_id,
            'status' => $request->status,
            'environment' => $request->environment,
            'search' => $request->search,
        ];

        $apiKeys = $this->apiKeyService->getApiKeys($filters);

        // Transform the collection to use ApiKeyResource
        $apiKeys = $apiKeys->setCollection($apiKeys->getCollection()->map(function ($item) {
            return new \App\Http\Resources\ApiKeyResource($item);
        }));

        return $this->pagination(
            paginator: $apiKeys,
            message: __('messages.api_keys_retrieved')
        );
    }

    public function store(CreateApiKeyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $clientId = $request->client_id;

            // Authorization: Only system owners can create API keys for other clients
            if ($user->isClientUser()) {
                if ($clientId && $clientId != $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
                $clientId = $user->getClientId();
            }

            $result = $this->apiKeyService->createApiKey(
                clientId: $clientId,
                keyName: $request->key_name,
                environment: $request->environment,
                ipWhitelist: $request->ip_whitelist,
                rateLimitPerMinute: $request->rate_limit_per_minute ?? 60,
                rateLimitPerHour: $request->rate_limit_per_hour ?? 1000,
                notes: $request->notes,
            );

            return $this->created(
                data: [
                    'api_key' => new \App\Http\Resources\ApiKeyResource($result['api_key']),
                    'credentials' => [
                        'api_key' => $result['credentials']['api_key'],
                        'api_secret' => $result['credentials']['api_secret'],
                        'warning' => 'Please save these credentials securely. You will not be able to see the secret again.',
                    ],
                ],
                message: __('messages.api_key_created')
            );
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_key_create_error')
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $apiKey = $this->apiKeyService->getApiKeyById($id);

            return $this->success(
                data: new \App\Http\Resources\ApiKeyResource($apiKey),
                message: __('messages.api_key_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        }
    }

    public function update(int $id, UpdateApiKeyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // First, check if the user can access this API key
            $apiKey = ApiKey::findOrFail($id);

            // Authorization: Only system owners can update any API key
            if (!$user->isSystemOwner()) {
                // If not a system owner, user can only update their own client's API keys
                if ($user->isClientUser() && $apiKey->client_id != $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }

            $apiKey = $this->apiKeyService->updateApiKey(
                apiKeyId: $id,
                keyName: $request->key_name,
                ipWhitelist: $request->ip_whitelist,
                rateLimitPerMinute: $request->rate_limit_per_minute,
                rateLimitPerHour: $request->rate_limit_per_hour,
                notes: $request->notes,
            );

            return $this->updated(
                data: new \App\Http\Resources\ApiKeyResource($apiKey),
                message: __('messages.api_key_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_key_update_error')
            );
        }
    }

    public function revoke(int $id, Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // First, check if the user can access this API key
            $apiKey = ApiKey::findOrFail($id);

            // Authorization: Only system owners can revoke any API key
            if (!$user->isSystemOwner()) {
                // If not a system owner, user can only revoke their own client's API keys
                if ($user->isClientUser() && $apiKey->client_id != $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }

            $apiKey = $this->apiKeyService->revokeApiKey(
                apiKeyId: $id,
                reason: $request->reason,
            );

            return $this->success(
                data: new \App\Http\Resources\ApiKeyResource($apiKey),
                message: __('messages.api_key_revoked')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_key_revoke_error')
            );
        }
    }

    public function regenerateSecret(int $id): JsonResponse
    {
        try {
            $user = auth()->user();

            // First, check if the user can access this API key
            $apiKey = ApiKey::findOrFail($id);

            // Authorization: Only system owners can regenerate secret of any API key
            if (!$user->isSystemOwner()) {
                // If not a system owner, user can only regenerate secret for their own client's API keys
                if ($user->isClientUser() && $apiKey->client_id != $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }

            $result = $this->apiKeyService->regenerateApiSecret($id);

            return $this->success(
                data: [
                    'api_key' => new \App\Http\Resources\ApiKeyResource($result['api_key']),
                    'api_secret' => $result['api_secret'], // This is the plain secret that was just generated
                    'warning' => 'Please save this new secret securely. You will not be able to see it again.',
                ],
                message: __('messages.api_secret_regenerated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_secret_regenerate_error')
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = auth()->user();

            // First, check if the user can access this API key
            $apiKey = ApiKey::findOrFail($id);

            // Authorization: Only system owners can toggle status of any API key
            if (!$user->isSystemOwner()) {
                // If not a system owner, user can only toggle their own client's API keys
                if ($user->isClientUser() && $apiKey->client_id != $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }

            $apiKey = $this->apiKeyService->toggleApiKeyStatus($id);

            return $this->success(
                data: new \App\Http\Resources\ApiKeyResource($apiKey),
                message: __('messages.api_key_status_toggled')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_key_toggle_error')
            );
        }
    }

    public function getByClient(int $clientId): JsonResponse
    {
        try {
            $apiKeys = $this->apiKeyService->getClientApiKeys($clientId);

            // Transform the collection to use ApiKeyResource
            $apiKeys = $apiKeys->setCollection($apiKeys->getCollection()->map(function ($item) {
                return new \App\Http\Resources\ApiKeyResource($item);
            }));

            return $this->pagination(
                paginator: $apiKeys,
                message: __('messages.client_api_keys_retrieved')
            );
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_keys_retrieve_error')
            );
        }
    }
}
