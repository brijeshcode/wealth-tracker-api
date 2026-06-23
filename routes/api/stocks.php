<?php

use App\Http\Controllers\Stocks\StockHoldingController;
use App\Http\Controllers\Stocks\StockLotController;
use App\Http\Controllers\Stocks\StockMasterController;
use App\Http\Controllers\Stocks\StockTaxController;
use App\Http\Controllers\Stocks\StockTransactionController;
use Illuminate\Support\Facades\Route;

// Stock master search — no auth required
Route::get('stocks/search',        [StockMasterController::class, 'search']);
Route::get('stocks/{stock}',       [StockMasterController::class, 'show']);
Route::get('stocks/{stock}/events', [StockMasterController::class, 'events']);

Route::middleware('auth:sanctum')->group(function () {
    // Holdings — no store; holdings are created automatically on first transaction
    Route::get('stock-holdings',                            [StockHoldingController::class, 'index']);
    Route::get('stock-holdings/{stockHolding}',             [StockHoldingController::class, 'show']);
    Route::put('stock-holdings/{stockHolding}',             [StockHoldingController::class, 'update']);
    Route::delete('stock-holdings/{stockHolding}',          [StockHoldingController::class, 'destroy']);
    Route::get('stock-holdings/{stockHolding}/computed',    [StockHoldingController::class, 'computed']);
    Route::get('stock-holdings/{stockHolding}/lots',        [StockLotController::class, 'index']);
    Route::get('stock-holdings/{stockHolding}/tax',         [StockTaxController::class, 'show']);

    // Transactions — store is flat (find-or-create holding); list is nested under holding
    Route::get('stock-holdings/{stockHolding}/transactions',    [StockTransactionController::class, 'index']);
    Route::post('stock-transactions',                           [StockTransactionController::class, 'store']);
    Route::put('stock-transactions/{transaction}',              [StockTransactionController::class, 'update']);
    Route::delete('stock-transactions/{transaction}',           [StockTransactionController::class, 'destroy']);
});
