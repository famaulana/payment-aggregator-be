<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', function (Request $request) {
        return $request->user();
    });
});
