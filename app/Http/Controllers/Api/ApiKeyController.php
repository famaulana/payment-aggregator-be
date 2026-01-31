<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateApiKeyRequest;
use App\Http\Requests\UpdateApiKeyRequest;
use App\Services\ApiKeyManagementService;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyManagementController extends Controller
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

        return $this->pagination(
            paginator: $apiKeys,
            message: __('messages.api_keys_retrieved')
        );
    }

    public function store(CreateApiKeyRequest $request): JsonResponse
    {
        try {
            $result = $this->apiKeyService->createApiKey(
                clientId: $request->client_id,
                keyName: $request->key_name,
                environment: $request->environment,
                ipWhitelist: $request->ip_whitelist,
                rateLimitPerMinute: $request->rate_limit_per_minute ?? 60,
                rateLimitPerHour: $request->rate_limit_per_hour ?? 1000,
                notes: $request->notes,
            );

            return $this->created(
                data: [
                    'api_key' => $result['api_key'],
                    'credentials' => [
                        'api_key' => $result['credentials']['api_key'],
                        'api_secret' => $result['credentials']['api_secret'],
                        'warning' => 'Please save these credentials securely. You will not be able to see the secret again.',
                    ],
                ],
                message: __('messages.api_key_created')
            );
        } catch (\Exception $e) {
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
                data: $apiKey,
                message: __('messages.api_key_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        }
    }

    public function update(int $id, UpdateApiKeyRequest $request): JsonResponse
    {
        try {
            $apiKey = $this->apiKeyService->updateApiKey(
                apiKeyId: $id,
                keyName: $request->key_name,
                ipWhitelist: $request->ip_whitelist,
                rateLimitPerMinute: $request->rate_limit_per_minute,
                rateLimitPerHour: $request->rate_limit_per_hour,
                notes: $request->notes,
            );

            return $this->updated(
                data: $apiKey,
                message: __('messages.api_key_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_key_update_error')
            );
        }
    }

    public function revoke(int $id, Request $request): JsonResponse
    {
        try {
            $apiKey = $this->apiKeyService->revokeApiKey(
                apiKeyId: $id,
                reason: $request->reason,
            );

            return $this->success(
                data: $apiKey,
                message: __('messages.api_key_revoked')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_key_revoke_error')
            );
        }
    }

    public function regenerateSecret(int $id): JsonResponse
    {
        try {
            $result = $this->apiKeyService->regenerateApiSecret($id);

            return $this->success(
                data: [
                    'api_key' => $result['api_key'],
                    'api_secret' => $result['api_secret'],
                    'warning' => 'Please save this new secret securely. You will not be able to see it again.',
                ],
                message: __('messages.api_secret_regenerated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_secret_regenerate_error')
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $apiKey = $this->apiKeyService->toggleApiKeyStatus($id);

            return $this->success(
                data: $apiKey,
                message: __('messages.api_key_status_toggled')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.api_key_not_found'));
        } catch (\Exception $e) {
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

            return $this->pagination(
                paginator: $apiKeys,
                message: __('messages.client_api_keys_retrieved')
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.api_keys_retrieve_error')
            );
        }
    }
}
