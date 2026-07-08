<?php

namespace App\Services\Stocks;

use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockPrice;
use App\Models\Stocks\StockPriceSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StockPriceSyncService
{
    public function sync(Carbon $date, string $triggeredBy = 'scheduler'): array
    {
        if ($date->isWeekend()) {
            return $this->log($date, $triggeredBy, 'skipped', 0, 'Weekend — market closed');
        }

        $heldStocks = $this->getHeldStocks();

        if ($heldStocks->isEmpty()) {
            return $this->log($date, $triggeredBy, 'skipped', 0, 'No active holdings to update');
        }

        $upserts = [];
        $errors  = [];

        foreach ($heldStocks as $stockId => $symbol) {
            try {
                $price = $this->fetchYahooPrice($symbol, $date);
                if ($price !== null) {
                    $upserts[] = [
                        'stock_id'    => $stockId,
                        'price_date'  => $date->toDateString(),
                        'period'      => 'daily',
                        'close_price' => $price['close'],
                        'volume'      => $price['volume'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = "{$symbol}: {$e->getMessage()}";
            }
        }

        if (! empty($upserts)) {
            StockPrice::upsert(
                $upserts,
                ['stock_id', 'price_date', 'period'],
                ['close_price', 'volume', 'updated_at']
            );
        }

        $count = count($upserts);

        if ($count === 0) {
            $msg    = empty($errors) ? 'No price data available for this date' : implode('; ', array_slice($errors, 0, 3));
            $status = empty($errors) ? 'skipped' : 'failed';
            return $this->log($date, $triggeredBy, $status, 0, $msg);
        }

        $message = "Updated {$count} stock prices";
        if (! empty($errors)) {
            $message .= '; ' . count($errors) . ' failed';
        }

        return $this->log($date, $triggeredBy, 'success', $count, $message);
    }

    private function getHeldStocks(): \Illuminate\Support\Collection
    {
        return Stock::whereIn('id',
            StockHolding::withoutGlobalScopes()
                ->where('quantity', '>', 0)
                ->whereNull('deleted_at')
                ->select('stock_id')
        )->pluck('nse_symbol', 'id');
    }

    private function fetchYahooPrice(string $symbol, Carbon $date): ?array
    {
        $ticker = strtoupper($symbol) . '.NS';
        $url    = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?interval=1d&range=5d";

        $response = Http::withoutVerifying()->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept'     => 'application/json',
        ])->timeout(10)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Yahoo Finance returned HTTP {$response->status()} for {$ticker}");
        }

        $data   = $response->json();
        $result = $data['chart']['result'][0] ?? null;

        if (! $result) {
            return null;
        }

        $timestamps = $result['timestamp'] ?? [];
        $closes     = $result['indicators']['quote'][0]['close'] ?? [];
        $volumes    = $result['indicators']['quote'][0]['volume'] ?? [];

        foreach ($timestamps as $i => $ts) {
            if (Carbon::createFromTimestamp($ts)->toDateString() === $date->toDateString()) {
                $close = isset($closes[$i]) ? (float) $closes[$i] : null;
                if ($close === null) {
                    return null;
                }
                return ['close' => $close, 'volume' => (int) ($volumes[$i] ?? 0)];
            }
        }

        return null;
    }

    private function log(Carbon $date, string $triggeredBy, string $status, int $count, string $message): array
    {
        StockPriceSyncLog::updateOrCreate(
            ['price_date' => $date->toDateString(), 'triggered_by' => $triggeredBy],
            ['status' => $status, 'stocks_updated' => $count, 'message' => $message]
        );

        Log::info("StockPriceSync [{$status}] {$date->toDateString()}: {$message}");

        return ['status' => $status, 'stocks_updated' => $count, 'message' => $message];
    }
}
