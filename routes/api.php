<?php

use App\Http\Controllers\Dashboard\AuthController as DashboardAuthController;
use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\SystemOwnerController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\HeadQuarterController;
use App\Http\Controllers\Dashboard\MerchantController;
use App\Http\Controllers\Dashboard\LocationController;
use App\Http\Controllers\Dashboard\SimulatorController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\WebhookController;
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

            // System Owner management
            Route::prefix('system-owners')->group(function () {
                // Only the top-level system_owner role can create new system owners
                Route::middleware(['role:system_owner'])->group(function () {
                    Route::post('/', [SystemOwnerController::class, 'store']);
                });

                // All system_owner sub-roles can list, view, and self-update
                Route::middleware(['role:system_owner|system_owner_admin|system_owner_finance|system_owner_support'])->group(function () {
                    Route::get('/', [SystemOwnerController::class, 'index']);
                    Route::get('/{id}', [SystemOwnerController::class, 'show']);
                    Route::put('/{id}', [SystemOwnerController::class, 'update']);
                    Route::post('/{id}/toggle-status', [SystemOwnerController::class, 'toggleStatus']);
                });
            });

            Route::middleware(['role:system_owner|system_owner_admin'])->prefix('audit-logs')->group(function () {
                Route::get('/', [AuditLogController::class, 'index']);
                Route::get('/{id}', [AuditLogController::class, 'show']);
            });

            // Self-profile update — any authenticated dashboard user (no role restriction)
            Route::put('/users', [UserController::class, 'update']);

            Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_quarter'])->prefix('users')->group(function () {
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

            Route::prefix('head-quarters')->group(function () {
                // System Owner and Client can list, create, update, toggle
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin'])->group(function () {
                    Route::get('/', [HeadQuarterController::class, 'index']);
                    Route::post('/', [HeadQuarterController::class, 'store']);
                    Route::put('/{id}', [HeadQuarterController::class, 'update']);
                    Route::post('/{id}/toggle-status', [HeadQuarterController::class, 'toggleStatus']);
                });

                // System Owner, Client, and Head Quarter can view details (with access control)
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_quarter'])->group(function () {
                    Route::get('/{id}', [HeadQuarterController::class, 'show']);
                });
            });

            Route::prefix('merchants')->group(function () {
                // System Owner, Client, and Head Quarter can list, create, update, toggle
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_quarter'])->group(function () {
                    Route::get('/', [MerchantController::class, 'index']);
                    Route::post('/', [MerchantController::class, 'store']);
                    Route::put('/{id}', [MerchantController::class, 'update']);
                    Route::post('/{id}/toggle-status', [MerchantController::class, 'toggleStatus']);
                });

                // All roles can view merchant details (with access control)
                Route::middleware(['role:system_owner|system_owner_admin|client|client_admin|head_quarter|merchant'])->group(function () {
                    Route::get('/{id}', [MerchantController::class, 'show']);
                });
            });

            Route::prefix('locations')->group(function () {
                Route::get('/provinces', [LocationController::class, 'provinces']);
                Route::get('/cities', [LocationController::class, 'cities']);
                Route::get('/districts', [LocationController::class, 'districts']);
                Route::get('/sub-districts', [LocationController::class, 'subDistricts']);
            });

            // ─────────────────────────────────────────────────────────────────
            // Payment Simulator — for testing payment flows via dashboard
            // ─────────────────────────────────────────────────────────────────
            Route::middleware(['role:system_owner|system_owner_admin|client|client_admin'])
                ->prefix('simulator')
                ->group(function () {
                    Route::get('/transactions', [SimulatorController::class, 'transactions']);
                    Route::get('/transactions/{transactionId}', [SimulatorController::class, 'show']);
                    Route::post('/transactions/{transactionId}/pay', [SimulatorController::class, 'pay']);
                    Route::post('/transactions/{transactionId}/fail', [SimulatorController::class, 'fail']);
                    Route::post('/transactions/{transactionId}/expire', [SimulatorController::class, 'expire']);
                    Route::post('/transactions/{transactionId}/refund', [SimulatorController::class, 'refund']);
                });
        });
    });

    Route::post('/login', [ApiAuthController::class, 'login'])->middleware(['validate.ip', 'security.validation', 'log.api']);
    Route::middleware(['auth:api', 'require.api.key.whitelist', 'validate.ip', 'rate.limit.by.api.key', 'security.validation', 'log.api'])->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout']);
        Route::get('/me', [ApiAuthController::class, 'me']);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Payment Gateway Wrapper API
    // Auth: X-Api-Key + HMAC Signature (no OAuth)
    // ─────────────────────────────────────────────────────────────────────────

    // Inbound webhooks from PG — no client auth, validated by PG signature inside job
    Route::post('/webhooks/inbound/{gateway}', [WebhookController::class, 'inbound'])
        ->middleware(['security.validation']);

    // All other payment endpoints require API key auth
    Route::middleware([
        'require.api.key.whitelist',
        'validate.ip',
        'verify.signature',
        'rate.limit.by.api.key',
        'security.validation',
        'log.api',
    ])->group(function () {

        // Payments
        Route::post('/payments', [PaymentController::class, 'create']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{transactionId}', [PaymentController::class, 'show']);
        Route::post('/payments/{transactionId}/cancel', [PaymentController::class, 'cancel']);
        Route::post('/payments/{transactionId}/refund', [PaymentController::class, 'refund']);

        // Payment Methods
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

        // Balance
        Route::get('/balance', [BalanceController::class, 'show']);
        Route::get('/balance/history', [BalanceController::class, 'history']);

        // Webhook test
        Route::post('/webhooks/test', [WebhookController::class, 'test']);

        // Webhook simulator — for internal developer testing
        Route::prefix('webhooks/simulator')->group(function () {
            Route::get('/transactions', [WebhookController::class, 'simulatorTransactions']);
            Route::post('/trigger', [WebhookController::class, 'simulatorTrigger']);
        });
    });
});

