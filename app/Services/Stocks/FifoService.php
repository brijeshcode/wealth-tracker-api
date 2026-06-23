<?php

namespace App\Services\Stocks;

use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockLot;
use App\Models\Stocks\StockTransaction;
class FifoService
{
    /**
     * Create a lot row for a buy transaction.
     */
    public function createLot(StockTransaction $transaction): StockLot
    {
        return StockLot::create([
            'stock_holding_id'   => $transaction->stock_holding_id,
            'buy_transaction_id' => $transaction->id,
            'quantity_remaining' => $transaction->quantity,
            'is_exhausted'       => false,
        ]);
    }

    /**
     * Consume lots FIFO for a sell transaction.
     * Throws \RuntimeException if any lot in the path is locked.
     * Throws \RuntimeException if available quantity is insufficient.
     */
    public function consumeLots(StockHolding $holding, float $sellQty): void
    {
        $lots = StockLot::where('stock_holding_id', $holding->id)
            ->where('is_exhausted', false)
            ->orderBy(
                StockTransaction::select('transaction_date')
                    ->whereColumn('stock_transactions.id', 'stock_lots.buy_transaction_id')
                    ->limit(1)
            )
            ->lockForUpdate()
            ->get();

        $available = $lots->sum('quantity_remaining');
        if ($available < $sellQty) {
            throw new \RuntimeException(
                "Insufficient quantity. Available: {$available}, attempted sell: {$sellQty}."
            );
        }

        $remaining = $sellQty;

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            // Lock-in check
            if ($lot->locked_until && $lot->locked_until->isFuture()) {
                abort(422, "Lot {$lot->id} is locked until {$lot->locked_until->toDateString()}.");
            }

            $consume = min((float) $lot->quantity_remaining, $remaining);
            $newQty  = (float) $lot->quantity_remaining - $consume;

            $lot->update([
                'quantity_remaining' => $newQty,
                'is_exhausted'       => $newQty <= 0,
            ]);

            $remaining -= $consume;
        }
    }

    /**
     * Restore lots when a sell transaction is deleted or edited down.
     * Walks FIFO in reverse order (youngest lot first) and restores qty.
     */
    public function restoreLots(StockHolding $holding, float $restoreQty): void
    {
        $lots = StockLot::where('stock_holding_id', $holding->id)
            ->orderByDesc(
                StockTransaction::select('transaction_date')
                    ->whereColumn('stock_transactions.id', 'stock_lots.buy_transaction_id')
                    ->limit(1)
            )
            ->get();

        $remaining = $restoreQty;

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $buyTxn       = $lot->buyTransaction;
            $originalQty  = (float) $buyTxn->quantity;
            $currentQty   = (float) $lot->quantity_remaining;
            $deficit      = $originalQty - $currentQty;

            if ($deficit <= 0) {
                continue;
            }

            $restore = min($deficit, $remaining);
            $newQty  = $currentQty + $restore;

            $lot->update([
                'quantity_remaining' => $newQty,
                'is_exhausted'       => false,
            ]);

            $remaining -= $restore;
        }
    }
}
