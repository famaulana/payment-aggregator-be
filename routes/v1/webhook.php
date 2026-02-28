<?php

use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/inbound/{gateway}', [WebhookController::class, 'inbound'])
    ->middleware(['security.validation']);

Route::middleware([
    'require.api.key.whitelist',
    'validate.ip',
    'verify.signature',
    'rate.limit.by.api.key',
    'security.validation',
    'log.api',
])->group(function () {
    Route::post('/webhooks/test', [WebhookController::class, 'test']);

    Route::prefix('webhooks/simulator')->group(function () {
        Route::get('/transactions', [WebhookController::class, 'simulatorTransactions']);
        Route::post('/trigger', [WebhookController::class, 'simulatorTrigger']);
    });
});
