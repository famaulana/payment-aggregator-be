<?php

use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\SystemOwnerController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\HeadQuarterController;
use App\Http\Controllers\Dashboard\MerchantController;
use App\Http\Controllers\Dashboard\LocationController;
use App\Http\Controllers\Dashboard\SimulatorController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::middleware(['role.group:is_system_owner|is_client'])->prefix('api-keys')->group(function () {
            Route::get('/', [ApiKeyController::class, 'index']);
            Route::post('/', [ApiKeyController::class, 'store']);
            Route::get('/{id}', [ApiKeyController::class, 'show']);
            Route::put('/{id}', [ApiKeyController::class, 'update']);
            Route::post('/{id}/revoke', [ApiKeyController::class, 'revoke']);
            Route::post('/{id}/regenerate-secret', [ApiKeyController::class, 'regenerateSecret']);
            Route::post('/{id}/toggle-status', [ApiKeyController::class, 'toggleStatus']);
            Route::get('/client/{clientId}', [ApiKeyController::class, 'getByClient']);
        });

        Route::middleware(['role.group:is_system_owner'])->prefix('system-owners')->group(function () {
            Route::get('/', [SystemOwnerController::class, 'index']);
            Route::get('/{id}', [SystemOwnerController::class, 'show']);
            // Route::post('/', [SystemOwnerController::class, 'store']); // Disable for now
            // Route::put('/{id}', [SystemOwnerController::class, 'update']); // Disable for now
            // Route::post('/{id}/toggle-status', [SystemOwnerController::class, 'toggleStatus']); // Disable for now
        });

        Route::middleware(['role.group:is_system_owner'])->prefix('audit-logs')->group(function () {
            Route::get('/', [AuditLogController::class, 'index']);
            Route::get('/{id}', [AuditLogController::class, 'show']);
        });

        Route::put('/users', [UserController::class, 'update']);

        Route::middleware(['role.group:is_system_owner|is_client|is_head_quarter'])->prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/with-entity', [UserController::class, 'storeWithEntity']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
            // Route::post('/', [UserController::class, 'store']); // Disable for now
        });

        Route::middleware(['role.group:is_system_owner|is_client'])->prefix('clients')->group(function () {
            Route::get('/', [ClientController::class, 'index']);
            Route::get('/{id}', [ClientController::class, 'show']);
            // Route::post('/', [ClientController::class, 'store']); // Disable for now
            // Route::put('/{id}', [ClientController::class, 'update']); // Disable for now
            // Route::post('/{id}/toggle-status', [ClientController::class, 'toggleStatus']); // Disable for now
        });

        Route::middleware(['role.group:is_system_owner|is_client|is_head_quarter'])->prefix('head-quarters')->group(function () {
            Route::get('/', [HeadQuarterController::class, 'index']);
            Route::get('/{id}', [HeadQuarterController::class, 'show']);
            // Route::post('/', [HeadQuarterController::class, 'store']); // Disable for now
            // Route::put('/{id}', [HeadQuarterController::class, 'update']); // Disable for now
            // Route::post('/{id}/toggle-status', [HeadQuarterController::class, 'toggleStatus']); // Disable for now
        });

        Route::middleware(['role.group:is_system_owner|is_client|is_head_quarter|is_merchant'])->prefix('merchants')->group(function () {
            Route::get('/', [MerchantController::class, 'index']);
            Route::get('/{id}', [MerchantController::class, 'show']);
            // Route::post('/', [MerchantController::class, 'store']); // Disable for now
            // Route::put('/{id}', [MerchantController::class, 'update']); // Disable for now
            // Route::post('/{id}/toggle-status', [MerchantController::class, 'toggleStatus']); // Disable for now
        });

        Route::prefix('locations')->group(function () {
            Route::get('/provinces', [LocationController::class, 'provinces']);
            Route::get('/cities', [LocationController::class, 'cities']);
            Route::get('/districts', [LocationController::class, 'districts']);
            Route::get('/sub-districts', [LocationController::class, 'subDistricts']);
        });

        Route::middleware(['role.group:is_system_owner|is_client'])->prefix('simulator')->group(function () {
            Route::get('/transactions', [SimulatorController::class, 'transactions']);
            Route::get('/transactions/{transactionId}', [SimulatorController::class, 'show']);
            Route::post('/transactions/{transactionId}/pay', [SimulatorController::class, 'pay']);
            Route::post('/transactions/{transactionId}/fail', [SimulatorController::class, 'fail']);
            Route::post('/transactions/{transactionId}/expire', [SimulatorController::class, 'expire']);
            Route::post('/transactions/{transactionId}/refund', [SimulatorController::class, 'refund']);
        });
    });
});
