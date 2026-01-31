<?php

use App\Http\Controllers\Dashboard\AuthController as DashboardAuthController;
use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::prefix('oauth')->group(function () {
//     Route::post('/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
// });

Route::prefix('v1')->group(function () {

    Route::prefix('dashboard')->group(function () {
        Route::post('/login', [DashboardAuthController::class, 'login']);
        Route::post('/refresh', [DashboardAuthController::class, 'refresh']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [DashboardAuthController::class, 'logout']);
            Route::post('/logout-all', [DashboardAuthController::class, 'logoutAll']);
            Route::get('/me', [DashboardAuthController::class, 'me']);

            Route::middleware(['role:system_owner,system_owner_admin,client'])->prefix('api-keys')->group(function () {
                Route::get('/', [ApiKeyController::class, 'index']);
                Route::post('/', [ApiKeyController::class, 'store']);
                Route::get('/{id}', [ApiKeyController::class, 'show']);
                Route::put('/{id}', [ApiKeyController::class, 'update']);
                Route::post('/{id}/revoke', [ApiKeyController::class, 'revoke']);
                Route::post('/{id}/regenerate-secret', [ApiKeyController::class, 'regenerateSecret']);
                Route::post('/{id}/toggle-status', [ApiKeyController::class, 'toggleStatus']);
                Route::get('/client/{clientId}', [ApiKeyController::class, 'getByClient']);
            });

            Route::middleware(['role:system_owner,system_owner_admin'])->prefix('audit-logs')->group(function () {
                Route::get('/', [AuditLogController::class, 'index']);
                Route::get('/{id}', [AuditLogController::class, 'show']);
            });
        });
    });

    Route::post('/login', [ApiAuthController::class, 'login'])->middleware(['validate.ip', 'log.api']);
    Route::middleware(['auth:api', 'log.api'])->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout']);
        Route::get('/me', [ApiAuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
