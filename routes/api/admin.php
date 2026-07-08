<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StockMasterController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::get('me', [AdminController::class, 'me']);

    // Stock master
    Route::get('stocks',                         [StockMasterController::class, 'index']);
    Route::put('stocks/{stock}',                 [StockMasterController::class, 'update']);
    Route::patch('stocks/{stock}/toggle-active', [StockMasterController::class, 'toggleActive']);
    Route::post('stocks/import-nse',             [StockMasterController::class, 'importNse']);
    Route::post('stocks/import-nse-etf',         [StockMasterController::class, 'importNseEtf']); // build after confirming ETF CSV format
    Route::post('stocks/upsert',                 [StockMasterController::class, 'upsert']);

});
