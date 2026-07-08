<?php

namespace App\Services\Stocks;

use App\Models\Stocks\StockPrice;
use App\Models\Stocks\StockPriceSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockPriceRollupService
{
    public function rollup(): int
    {
        $cutoff = Carbon::now()->subYear()->toDateString();

        // Pull qualifying rows into PHP to avoid YEARWEEK (MySQL-only)
        $dailyPrices = StockPrice::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
            ->where('period', 'daily')
            ->where('price_date', '<', $cutoff)
            ->whereNull('deleted_at')
            ->orderBy('stock_id')
            ->orderBy('price_date')
            ->get();

        // Group by stock_id + ISO year-week (Carbon format 'oW' = ISO year + zero-padded week)
        $groups = $dailyPrices->groupBy(function ($row) {
            $date = $row->price_date instanceof Carbon
                ? $row->price_date
                : Carbon::parse($row->price_date);

            return $row->stock_id . '_' . $date->format('oW');
        });

        $weeksRolled = 0;

        foreach ($groups as $rows) {
            $lastRow = $rows->sortByDesc('price_date')->first();

            DB::transaction(function () use ($rows, $lastRow) {
                StockPrice::upsert([
                    [
                        'stock_id'    => $lastRow->stock_id,
                        'price_date'  => $lastRow->price_date instanceof Carbon
                            ? $lastRow->price_date->toDateString()
                            : Carbon::parse($lastRow->price_date)->toDateString(),
                        'period'      => 'weekly',
                        'close_price' => $lastRow->close_price,
                        'volume'      => $rows->sum('volume'),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ],
                ],
                ['stock_id', 'price_date', 'period'],
                ['close_price', 'volume', 'updated_at']);

                StockPrice::whereIn('id', $rows->pluck('id'))->delete();
            });

            $weeksRolled++;
        }

        return $weeksRolled;
    }

    public function cleanSuccessLogs(): int
    {
        return StockPriceSyncLog::where('status', 'success')
            ->where('price_date', '<', Carbon::now()->subMonth()->toDateString())
            ->delete();
    }
}
