<?php

namespace App\Services;

use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseService
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        ResponseCode $code = ResponseCode::SUCCESS
    ): JsonResponse {
        $response = [
            'response_code' => $code->value,
            'response_message' => $message ?? __($code->getMessage()),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code->getHttpStatusCode());
    }

    public static function successWithMeta(
        mixed $data,
        array $meta,
        ?string $message = null,
        ResponseCode $code = ResponseCode::SUCCESS
    ): JsonResponse {
        $response = [
            'response_code' => $code->value,
            'response_message' => $message ?? __($code->getMessage()),
            'data' => $data,
            'meta' => $meta,
        ];

        return response()->json($response, $code->getHttpStatusCode());
    }

    public static function pagination(
        LengthAwarePaginator $paginator,
        ?string $message = null,
        ResponseCode $code = ResponseCode::SUCCESS
    ): JsonResponse {
        $response = [
            'response_code' => $code->value,
            'response_message' => $message ?? __($code->getMessage()),
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        return response()->json($response, $code->getHttpStatusCode());
    }

    public static function paginationWithResource(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        ?string $message = null,
        ResponseCode $code = ResponseCode::SUCCESS
    ): JsonResponse {
        $collection = $resourceClass::collection($paginator);

        $response = [
            'response_code' => $code->value,
            'response_message' => $message ?? __($code->getMessage()),
            'data' => $collection->resolve(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        return response()->json($response, $code->getHttpStatusCode());
    }

    public static function error(
        ResponseCode $code,
        ?string $message = null,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'response_code' => $code->value,
            'response_message' => $message ?? __($code->getMessage()),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code->getHttpStatusCode());
    }

    public static function validationError(
        array $errors,
        ?string $message = null
    ): JsonResponse {
        $response = [
            'response_code' => ResponseCode::VALIDATION_ERROR->value,
            'response_message' => $message ?? __('messages.validation_error'),
            'errors' => $errors,
        ];

        return response()->json($response, ResponseCode::VALIDATION_ERROR->getHttpStatusCode());
    }

    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error(
            ResponseCode::NOT_FOUND,
            $message ?? __('messages.not_found')
        );
    }

    public static function unauthorized(?string $message = null): JsonResponse
    {
        return self::error(
            ResponseCode::UNAUTHORIZED,
            $message ?? __('messages.unauthorized')
        );
    }

    public static function forbidden(?string $message = null): JsonResponse
    {
        return self::error(
            ResponseCode::FORBIDDEN,
            $message ?? __('messages.forbidden')
        );
    }

    public static function serverError(
        ?string $message = null,
        ?\Throwable $exception = null
    ): JsonResponse {
        $response = [
            'response_code' => ResponseCode::INTERNAL_SERVER_ERROR->value,
            'response_message' => $message ?? __('messages.internal_server_error'),
        ];

        if ($exception && config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return response()->json($response, ResponseCode::INTERNAL_SERVER_ERROR->getHttpStatusCode());
    }

    public static function created(
        mixed $data,
        ?string $message = null
    ): JsonResponse {
        return self::success(
            $data,
            $message ?? __('messages.resource_created'),
            ResponseCode::CREATED
        );
    }

    public static function updated(
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        return self::success(
            $data,
            $message ?? __('messages.resource_updated'),
            ResponseCode::UPDATED
        );
    }

    public static function deleted(?string $message = null): JsonResponse
    {
        return self::success(
            null,
            $message ?? __('messages.resource_deleted'),
            ResponseCode::DELETED
        );
    }
}
