<?php

namespace App\Services\Stocks;

use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockLot;
use App\Models\Stocks\StockTransaction;

class TaxCalculator
{
    private const STCG_RATE  = 0.15;
    private const LTCG_RATE  = 0.10;
    private const LTCG_EXEMPTION = 100000; // ₹1 lakh per financial year

    /**
     * Compute STCG / LTCG breakdown for all exhausted lots of a holding.
     * Holding period < 1 year from buy_date → STCG @ 15%
     * Holding period ≥ 1 year                → LTCG @ 10% on gains above ₹1 lakh
     *
     * Returns computed figures only — nothing is stored.
     */
    public function compute(StockHolding $holding): array
    {
        $lots = StockLot::where('stock_holding_id', $holding->id)
            ->where('is_exhausted', true)
            ->with('buyTransaction')
            ->get();

        $stcgGain  = 0;
        $ltcgGain  = 0;
        $stcgTax   = 0;
        $ltcgTax   = 0;
        $breakdown = [];

        foreach ($lots as $lot) {
            $buyTxn = $lot->buyTransaction;
            if (! $buyTxn) {
                continue;
            }

            // Find sell transactions that consumed this lot.
            // We approximate: the lot was exhausted, so total sold = original buy qty.
            // Sell price is taken from the most recent sell transaction after buy date.
            $sellTxn = StockTransaction::where('stock_holding_id', $holding->id)
                ->where('type', 'sell')
                ->where('transaction_date', '>=', $buyTxn->transaction_date)
                ->withTrashed()
                ->orderByDesc('transaction_date')
                ->first();

            if (! $sellTxn) {
                continue;
            }

            $buyDate  = $buyTxn->transaction_date;
            $sellDate = $sellTxn->transaction_date;
            $months   = $buyDate->diffInMonths($sellDate);

            $qty       = (float) $buyTxn->quantity;
            $costBasis = $qty * (float) $buyTxn->price_per_unit;
            $proceeds  = $qty * (float) $sellTxn->price_per_unit;
            $gain      = $proceeds - $costBasis;

            $isLtcg = $months >= 12;

            if ($isLtcg) {
                $ltcgGain += $gain;
            } else {
                $stcgGain += $gain;
            }

            $breakdown[] = [
                'lot_id'            => $lot->id,
                'buy_date'          => $buyDate->toDateString(),
                'sell_date'         => $sellDate->toDateString(),
                'holding_months'    => $months,
                'quantity'          => $qty,
                'buy_price'         => round((float) $buyTxn->price_per_unit, 4),
                'sell_price'        => round((float) $sellTxn->price_per_unit, 4),
                'gain'              => round($gain, 2),
                'type'              => $isLtcg ? 'LTCG' : 'STCG',
            ];
        }

        $stcgTax = max(0, $stcgGain) * self::STCG_RATE;
        // LTCG exemption is applied at portfolio level — returned separately so the
        // caller (or the tax report endpoint) can aggregate across holdings first.
        $ltcgTaxableGain = max(0, $ltcgGain - self::LTCG_EXEMPTION);
        $ltcgTax         = $ltcgTaxableGain * self::LTCG_RATE;

        return [
            'stcg_gain'          => round($stcgGain, 2),
            'stcg_tax'           => round($stcgTax, 2),
            'ltcg_gain'          => round($ltcgGain, 2),
            'ltcg_exemption'     => self::LTCG_EXEMPTION,
            'ltcg_taxable_gain'  => round($ltcgTaxableGain, 2),
            'ltcg_tax'           => round($ltcgTax, 2),
            'total_tax'          => round($stcgTax + $ltcgTax, 2),
            'breakdown'          => $breakdown,
        ];
    }
}
