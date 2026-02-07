<?php

use App\Http\Controllers\Dashboard\AuthController as DashboardAuthController;
use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\HeadOfficeController;
use App\Http\Controllers\Dashboard\MerchantController;
use App\Http\Controllers\Dashboard\LocationController;
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

            Route::middleware(['role:system_owner|system_owner_admin|client'])->prefix('api-keys')->group(function () {
                Route::get('/', [ApiKeyController::class, 'index']);
                Route::post('/', [ApiKeyController::class, 'store']);
                Route::get('/{id}', [ApiKeyController::class, 'show']);
                Route::put('/{id}', [ApiKeyController::class, 'update']);
                Route::post('/{id}/revoke', [ApiKeyController::class, 'revoke']);
                Route::post('/{id}/regenerate-secret', [ApiKeyController::class, 'regenerateSecret']);
                Route::post('/{id}/toggle-status', [ApiKeyController::class, 'toggleStatus']);
                Route::get('/client/{clientId}', [ApiKeyController::class, 'getByClient']);
            });

            Route::middleware(['role:system_owner|system_owner_admin'])->prefix('audit-logs')->group(function () {
                Route::get('/', [AuditLogController::class, 'index']);
                Route::get('/{id}', [AuditLogController::class, 'show']);
            });

            Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_office'])->prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'index']);
                Route::post('/', [UserController::class, 'store']);
                Route::post('/with-entity', [UserController::class, 'storeWithEntity']);
                Route::get('/{id}', [UserController::class, 'show']);
                Route::put('/{id}', [UserController::class, 'update']);
                Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
                Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
            });

            Route::prefix('clients')->group(function () {
                // Only System Owner can list, create, update, toggle status
                Route::middleware(['role:system_owner|system_owner_admin'])->group(function () {
                    Route::get('/', [ClientController::class, 'index']);
                    Route::post('/', [ClientController::class, 'store']);
                    Route::put('/{id}', [ClientController::class, 'update']);
                    Route::post('/{id}/toggle-status', [ClientController::class, 'toggleStatus']);
                });

                // System Owner and Client can view client details (Client can only view own)
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin'])->group(function () {
                    Route::get('/{id}', [ClientController::class, 'show']);
                });
            });

            Route::prefix('head-offices')->group(function () {
                // System Owner and Client can list, create, update, toggle
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin'])->group(function () {
                    Route::get('/', [HeadOfficeController::class, 'index']);
                    Route::post('/', [HeadOfficeController::class, 'store']);
                    Route::put('/{id}', [HeadOfficeController::class, 'update']);
                    Route::post('/{id}/toggle-status', [HeadOfficeController::class, 'toggleStatus']);
                });

                // System Owner, Client, and Head Office can view details (with access control)
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_office'])->group(function () {
                    Route::get('/{id}', [HeadOfficeController::class, 'show']);
                });
            });

            Route::prefix('merchants')->group(function () {
                // System Owner, Client, and Head Office can list, create, update, toggle
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_office'])->group(function () {
                    Route::get('/', [MerchantController::class, 'index']);
                    Route::post('/', [MerchantController::class, 'store']);
                    Route::put('/{id}', [MerchantController::class, 'update']);
                    Route::post('/{id}/toggle-status', [MerchantController::class, 'toggleStatus']);
                });

                // All roles can view merchant details (with access control)
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_office|merchant'])->group(function () {
                    Route::get('/{id}', [MerchantController::class, 'show']);
                });
            });

            Route::prefix('locations')->group(function () {
                Route::get('/provinces', [LocationController::class, 'provinces']);
                Route::get('/cities', [LocationController::class, 'cities']);
                Route::get('/districts', [LocationController::class, 'districts']);
                Route::get('/sub-districts', [LocationController::class, 'subDistricts']);
            });
        });
    });

    Route::post('/login', [ApiAuthController::class, 'login'])->middleware(['validate.ip', 'security.validation', 'log.api']);
    Route::middleware(['auth:api', 'require.api.key.whitelist', 'validate.ip', 'rate.limit.by.api.key', 'security.validation', 'log.api'])->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout']);
        Route::get('/me', [ApiAuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
