<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-language', function () {
    return response()->json([
        'message' => __('messages.welcome'),
        'locale' => app()->getLocale(),
        'validation_example' => __('validation.required', ['attribute' => 'email']),
        'auth_example' => __('auth.failed'),
    ]);
});

Route::middleware('auth:api')->group(function () {

    Route::get('/me', function (Request $request) {
        return $request->user();
    });
});
