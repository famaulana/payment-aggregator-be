<?php

namespace App\Http\Controllers;

use App\Services\ResponseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function success(
        mixed $data = null,
        ?string $message = null,
        ?\App\Enums\ResponseCode $code = null
    ): JsonResponse {
        return ResponseService::success($data, $message, $code ?? \App\Enums\ResponseCode::SUCCESS);
    }

    protected function successWithMeta(
        mixed $data,
        array $meta,
        ?string $message = null
    ): JsonResponse {
        return ResponseService::successWithMeta($data, $meta, $message);
    }

    protected function pagination(
        \Illuminate\Pagination\LengthAwarePaginator $paginator,
        ?string $message = null
    ): JsonResponse {
        return ResponseService::pagination($paginator, $message);
    }

    protected function error(
        \App\Enums\ResponseCode $code,
        ?string $message = null,
        ?array $errors = null
    ): JsonResponse {
        return ResponseService::error($code, $message, $errors);
    }

    protected function validationError(
        array $errors,
        ?string $message = null
    ): JsonResponse {
        return ResponseService::validationError($errors, $message);
    }

    protected function notFound(?string $message = null): JsonResponse
    {
        return ResponseService::notFound($message);
    }

    protected function unauthorized(?string $message = null): JsonResponse
    {
        return ResponseService::unauthorized($message);
    }

    protected function forbidden(?string $message = null): JsonResponse
    {
        return ResponseService::forbidden($message);
    }

    protected function created(
        mixed $data,
        ?string $message = null
    ): JsonResponse {
        return ResponseService::created($data, $message);
    }

    protected function updated(
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        return ResponseService::updated($data, $message);
    }

    protected function deleted(?string $message = null): JsonResponse
    {
        return ResponseService::deleted($message);
    }
}
