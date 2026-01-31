<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use App\Services\ResponseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            return $this->handleException($e, $request);
        });
    }

    protected function handleException(Throwable $e, Request $request): ?JsonResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($e);
        }

        return null;
    }

    protected function renderApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof AppException) {
            return ResponseService::error(
                $e->getResponseCode(),
                $e->getMessage(),
                $e->getErrors()
            );
        }

        if ($e instanceof ValidationException) {
            return ResponseService::validationError(
                $e->errors(),
                $e->getMessage()
            );
        }

        if ($e instanceof AuthenticationException) {
            return ResponseService::unauthorized($e->getMessage());
        }

        if ($e instanceof AuthorizationException) {
            return ResponseService::forbidden($e->getMessage());
        }

        if ($e instanceof ModelNotFoundException) {
            return ResponseService::notFound($this->getModelNotFoundMessage($e));
        }

        if ($e instanceof NotFoundHttpException) {
            return ResponseService::error(
                ResponseCode::ENDPOINT_NOT_FOUND,
                __('messages.endpoint_not_found')
            );
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return ResponseService::error(
                ResponseCode::INVALID_INPUT,
                __('messages.method_not_allowed', [], $e->getMessage())
            );
        }

        if ($e instanceof TokenMismatchException) {
            return ResponseService::error(
                ResponseCode::TOKEN_EXPIRED,
                __('messages.session_expired')
            );
        }

        if ($e instanceof HttpException) {
            $responseCode = $this->getHttpExceptionCode($e->getStatusCode());
            return ResponseService::error(
                $responseCode,
                $e->getMessage() ?: __($responseCode->getMessage())
            );
        }

        return $this->renderServerError($e);
    }

    protected function renderServerError(Throwable $e): JsonResponse
    {
        $message = __('messages.internal_server_error');

        if (config('app.debug')) {
            $debug = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->map(fn ($trace) => array_intersect_key($trace, array_flip(['file', 'line', 'function', 'class'])))->all(),
            ];

            return response()->json([
                'response_code' => ResponseCode::INTERNAL_SERVER_ERROR->value,
                'response_message' => $message,
                'debug' => $debug,
            ], 500);
        }

        return ResponseService::serverError($message, $e);
    }

    protected function getHttpExceptionCode(int $statusCode): ResponseCode
    {
        return match($statusCode) {
            401 => ResponseCode::UNAUTHORIZED,
            403 => ResponseCode::FORBIDDEN,
            404 => ResponseCode::NOT_FOUND,
            422 => ResponseCode::VALIDATION_ERROR,
            429 => ResponseCode::TOO_MANY_REQUESTS,
            500 => ResponseCode::INTERNAL_SERVER_ERROR,
            503 => ResponseCode::SERVICE_UNAVAILABLE,
            default => ResponseCode::INTERNAL_SERVER_ERROR,
        };
    }

    protected function getModelNotFoundMessage(ModelNotFoundException $e): string
    {
        $model = class_basename($e->getModel());

        return match($model) {
            'User' => __('messages.user_not_found'),
            'Client' => __('messages.client_not_found'),
            'Transaction' => __('messages.transaction_not_found'),
            'Merchant' => __('messages.merchant_not_found'),
            default => __('messages.resource_not_found'),
        };
    }
}
