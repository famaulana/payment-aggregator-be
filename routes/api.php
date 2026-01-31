<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiKeyManagementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Passport OAuth Routes - Manual registration for Laravel 12
Route::prefix('oauth')->group(function () {
    Route::post('/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
});

Route::prefix('v1')->group(function () {
    // Public routes - Auth
    Route::prefix('auth')->group(function () {
        // FE Dashboard Authentication
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // API Server Authentication (API Key based)
        Route::post('/api/login', [ApiAuthController::class, 'login']);
    });

    // Protected routes - require authentication
    Route::middleware('auth:api')->group(function () {
        // FE Dashboard Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/tokens', [AuthController::class, 'tokens']);
        });

        // API Server Auth
        Route::prefix('auth/api')->group(function () {
            Route::post('/logout', [ApiAuthController::class, 'logout']);
            Route::get('/me', [ApiAuthController::class, 'me']);
        });

        // API Key Management (System Owner only)
        Route::middleware(['role:system_owner,system_owner_admin'])->prefix('api-keys')->group(function () {
            Route::get('/', [ApiKeyManagementController::class, 'index']);
            Route::post('/', [ApiKeyManagementController::class, 'store']);
            Route::get('/{id}', [ApiKeyManagementController::class, 'show']);
            Route::put('/{id}', [ApiKeyManagementController::class, 'update']);
            Route::post('/{id}/revoke', [ApiKeyManagementController::class, 'revoke']);
            Route::post('/{id}/regenerate-secret', [ApiKeyManagementController::class, 'regenerateSecret']);
            Route::post('/{id}/toggle-status', [ApiKeyManagementController::class, 'toggleStatus']);
            Route::get('/client/{clientId}', [ApiKeyManagementController::class, 'getByClient']);
        });

        // Audit Trails (System Owner only)
        Route::middleware(['role:system_owner,system_owner_admin'])->prefix('audit-logs')->group(function () {
            Route::get('/', [AuditLogController::class, 'index']);
            Route::get('/{id}', [AuditLogController::class, 'show']);
        });
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
