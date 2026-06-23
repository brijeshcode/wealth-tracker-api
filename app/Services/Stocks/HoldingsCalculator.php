<?php

namespace App\Services\Stocks;

use App\Models\Stocks\StockHolding;

class HoldingsCalculator
{
    /**
     * Recompute quantity and avg_buy_price from scratch, then sync both
     * the stock_holding and the parent holdings record.
     *
     * Always called after any transaction create / update / delete.
     */
    public function sync(StockHolding $holding): void
    {
        $holding->loadMissing('transactions');

        $qty = 0.0;
        $avg = 0.0;

        $transactions = $holding->transactions()
            ->withoutTrashed()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $txn) {
            if ($txn->type === 'buy') {
                $newQty   = (float) $txn->quantity;
                $newPrice = (float) $txn->price_per_unit;
                // Weighted average only updates on buys; sells leave avg unchanged
                $avg = ($qty + $newQty) > 0
                    ? ($qty * $avg + $newQty * $newPrice) / ($qty + $newQty)
                    : 0;
                $qty += $newQty;
            } elseif ($txn->type === 'sell') {
                $qty -= (float) $txn->quantity;
            }
        }

        $qty         = max(0.0, $qty);
        $avgBuyPrice = $avg;
        $principal   = $qty * $avgBuyPrice;

        $holding->update([
            'quantity'      => $qty,
            'avg_buy_price' => $avgBuyPrice,
        ]);

        $holding->holding()->update([
            'principal_amount' => $principal,
        ]);
    }

    /**
     * Return computed metrics for the holding — qty, avg, cost basis, P&L.
     * current_value and unrealized_pnl are null when no price row exists.
     */
    public function compute(StockHolding $holding): array
    {
        $holding->loadMissing(['stock.latestPrice']);

        $quantity     = (float) $holding->quantity;
        $avgBuyPrice  = (float) $holding->avg_buy_price;
        $costBasis    = $quantity * $avgBuyPrice;
        $latestPrice  = $holding->stock->latestPrice;

        $currentPrice    = $latestPrice ? (float) $latestPrice->close_price : null;
        $priceDate       = $latestPrice?->price_date?->toDateString();
        $currentValue    = $currentPrice !== null ? $quantity * $currentPrice : null;
        $unrealizedPnl   = $currentValue !== null ? $currentValue - $costBasis : null;
        $unrealizedPnlPct = ($costBasis > 0 && $unrealizedPnl !== null)
            ? round(($unrealizedPnl / $costBasis) * 100, 2)
            : null;

        return [
            'quantity'           => $quantity,
            'avg_buy_price'      => round($avgBuyPrice, 4),
            'cost_basis'         => round($costBasis, 2),
            'current_price'      => $currentPrice,
            'price_date'         => $priceDate,
            'current_value'      => $currentValue !== null ? round($currentValue, 2) : null,
            'unrealized_pnl'     => $unrealizedPnl !== null ? round($unrealizedPnl, 2) : null,
            'unrealized_pnl_pct' => $unrealizedPnlPct,
        ];
    }
}
