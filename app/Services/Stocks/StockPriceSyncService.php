<?php

namespace App\Services\Stocks;

use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockPrice;
use App\Models\Stocks\StockPriceSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class StockPriceSyncService
{
    public function sync(Carbon $date, string $triggeredBy = 'scheduler'): array
    {
        if ($date->isWeekend()) {
            return $this->log($date, $triggeredBy, 'skipped', 0, 'Weekend — NSE closed');
        }

        try {
            $csvContent = $this->downloadBhavcopy($date);
        } catch (\Exception $e) {
            return $this->log($date, $triggeredBy, 'failed', 0, $e->getMessage());
        }

        $heldIsins = $this->getHeldIsins();

        if ($heldIsins->isEmpty()) {
            return $this->log($date, $triggeredBy, 'skipped', 0, 'No active holdings to update');
        }

        $count = $this->upsertPrices($csvContent, $heldIsins, $date);

        return $this->log($date, $triggeredBy, 'success', $count, "Updated {$count} stock prices");
    }

    private function downloadBhavcopy(Carbon $date): string
    {
        $year     = $date->format('Y');
        $mon      = strtoupper($date->format('M'));
        $datePart = $date->format('d') . $mon . $year;
        $url      = "https://nseindia.com/content/historical/EQUITIES/{$year}/{$mon}/cm{$datePart}bhav.csv.zip";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Referer'    => 'https://www.nseindia.com/',
            'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ])->timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("NSE returned HTTP {$response->status()} for {$url}");
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'bhav_') . '.zip';
        file_put_contents($tmpZip, $response->body());

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            unlink($tmpZip);
            throw new \RuntimeException('Failed to open Bhavcopy ZIP file');
        }

        $csvContent = $zip->getFromIndex(0);
        $zip->close();
        unlink($tmpZip);

        if ($csvContent === false) {
            throw new \RuntimeException('Bhavcopy ZIP is empty');
        }

        return $csvContent;
    }

    private function getHeldIsins(): \Illuminate\Support\Collection
    {
        return Stock::whereIn('id',
            StockHolding::withoutGlobalScopes()
                ->where('quantity', '>', 0)
                ->whereNull('deleted_at')
                ->select('stock_id')
        )->pluck('isin', 'id');
    }

    private function upsertPrices(string $csvContent, \Illuminate\Support\Collection $heldIsins, Carbon $date): int
    {
        $csv = Reader::createFromString($csvContent);
        $csv->setHeaderOffset(0);

        $isinToStockId = $heldIsins->flip();

        $upserts = [];

        foreach ($csv->getRecords() as $row) {
            if (strtoupper(trim($row['SERIES'] ?? '')) !== 'EQ') {
                continue;
            }

            $isin = trim($row['ISIN'] ?? '');

            if (! isset($isinToStockId[$isin])) {
                continue;
            }

            $upserts[] = [
                'stock_id'    => $isinToStockId[$isin],
                'price_date'  => $date->toDateString(),
                'period'      => 'daily',
                'close_price' => (float) ($row['CLOSE'] ?? 0),
                'volume'      => (int) ($row['TOTTRDQTY'] ?? 0),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (empty($upserts)) {
            return 0;
        }

        StockPrice::upsert(
            $upserts,
            ['stock_id', 'price_date', 'period'],
            ['close_price', 'volume', 'updated_at']
        );

        return count($upserts);
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
