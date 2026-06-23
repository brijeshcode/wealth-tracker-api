<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Stocks\StockHolding;
use Illuminate\Http\JsonResponse;

class StockLotController extends Controller
{
    public function index(StockHolding $stockHolding): JsonResponse
    {
        $lots = $stockHolding->lots()
            ->with('buyTransaction')
            ->orderBy(
                \App\Models\Stocks\StockTransaction::select('transaction_date')
                    ->whereColumn('stock_transactions.id', 'stock_lots.buy_transaction_id')
                    ->limit(1)
            )
            ->get()
            ->map(fn ($lot) => [
                'id'                 => $lot->id,
                'buy_date'           => $lot->buyTransaction?->transaction_date?->toDateString(),
                'buy_price'          => $lot->buyTransaction?->price_per_unit,
                'original_quantity'  => $lot->buyTransaction?->quantity,
                'quantity_remaining' => $lot->quantity_remaining,
                'is_exhausted'       => $lot->is_exhausted,
                'is_locked'          => $lot->isLocked(),
                'locked_until'       => $lot->locked_until?->toDateString(),
            ]);

        return ApiResponse::index('Lots retrieved', $lots);
    }
}
