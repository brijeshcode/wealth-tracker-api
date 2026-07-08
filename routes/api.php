<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api/stocks.php';
require __DIR__.'/api/admin.php';

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('platforms', [PlatformController::class, 'index']);
});
