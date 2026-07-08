<?php

namespace App\Services\Stocks;

use App\Models\Holding;
use App\Models\Stocks\StockHolding;

class StockHoldingResolver
{
    public function resolve(
        int     $userId,
        int     $stockId,
        string  $exchange,
        int     $platformId,
        string  $transactionDate,
        ?string $nickname = null,
        ?string $notes = null,
    ): StockHolding {
        $existing = StockHolding::where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->where('exchange', $exchange)
            ->whereHas('holding', fn ($q) => $q->where('platform_id', $platformId))
            ->first();

        if ($existing) {
            return $existing;
        }

        $parent = Holding::create([
            'user_id'          => $userId,
            'platform_id'      => $platformId,
            'type'             => 'stock',
            'status'           => 'active',
            'principal_amount' => 0,
            'current_value'    => 0,
            'start_date'       => $transactionDate,
            'nickname'         => $nickname,
            'notes'            => $notes,
        ]);

        return StockHolding::create([
            'holding_id'    => $parent->id,
            'user_id'       => $userId,
            'stock_id'      => $stockId,
            'exchange'      => $exchange,
            'quantity'      => 0,
            'avg_buy_price' => 0,
        ]);
    }
}
