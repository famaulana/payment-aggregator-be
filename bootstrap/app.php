<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SetLocaleFromRequest::class);

        $middleware->alias([
            'auth.api' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'throttle.api' => \App\Http\Middleware\RateLimitMiddleware::class,
            'log.request' => \App\Http\Middleware\RequestLoggingMiddleware::class,
            'verify.signature' => \App\Http\Middleware\SignatureValidationMiddleware::class,
            'validate.ip' => \App\Http\Middleware\ValidateApiKeyIpWhitelist::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $handler = new \App\Exceptions\Handler(app());

            if ($request->expectsJson() || $request->is('api/*')) {
                return $handler->render($request, $e);
            }

            return null;
        });

        $exceptions->reportable(function (\Throwable $e) {
            if (config('app.env') !== 'testing') {
                \Log::error($e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    })->create();
