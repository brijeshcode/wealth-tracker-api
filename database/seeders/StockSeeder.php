<?php

namespace Database\Seeders;

use App\Services\Stocks\NseStockImportService;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(NseStockImportService::class);

        $equityPath = database_path('seeders/data/EQUITY_L.csv');

        if (file_exists($equityPath)) {
            $r = $service->importFromPath($equityPath);
            $this->command->info("Stocks (EQ): inserted={$r['inserted']}, updated={$r['updated']}, skipped={$r['skipped']}");
        } else {
            $this->command->warn('EQUITY_L.csv not found in database/seeders/data/ — skipping equity import.');
        }

        $etfPath = database_path('seeders/data/eq_etfseclist.csv');

        if (file_exists($etfPath)) {
            $r = $service->importEtfFromPath($etfPath);
            $this->command->info("Stocks (ETF): inserted={$r['inserted']}, updated={$r['updated']}, skipped={$r['skipped']}");
        } else {
            $this->command->warn('eq_etfseclist.csv not found in database/seeders/data/ — skipping ETF import.');
        }
    }
}
