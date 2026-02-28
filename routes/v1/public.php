<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\BalanceController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login'])->middleware(['validate.ip', 'security.validation', 'log.api']);
Route::middleware(['auth:api', 'require.api.key.whitelist', 'validate.ip', 'rate.limit.by.api.key', 'security.validation', 'log.api'])->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me', [ApiAuthController::class, 'me']);
});

Route::middleware([
    'require.api.key.whitelist',
    'validate.ip',
    'verify.signature',
    'rate.limit.by.api.key',
    'security.validation',
    'log.api',
])->group(function () {
    Route::post('/payments', [PaymentController::class, 'create']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{transactionId}', [PaymentController::class, 'show']);
    Route::post('/payments/{transactionId}/cancel', [PaymentController::class, 'cancel']);
    Route::post('/payments/{transactionId}/refund', [PaymentController::class, 'refund']);

    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

    Route::get('/balance', [BalanceController::class, 'show']);
    Route::get('/balance/history', [BalanceController::class, 'history']);
});
