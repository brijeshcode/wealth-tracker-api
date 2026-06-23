<?php

namespace Database\Seeders;

use App\Data\StockMasterData;
use App\Models\Stocks\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        foreach (StockMasterData::all() as $data) {
            Stock::updateOrCreate(
                ['isin' => $data['isin']],
                [
                    'company_name' => $data['company_name'],
                    'nse_symbol'   => $data['nse_symbol'] ?? null,
                    'bse_symbol'   => $data['bse_symbol'] ?? null,
                    'bse_code'     => $data['bse_code']   ?? null,
                    'sector'       => $data['sector']     ?? null,
                    'industry'     => $data['industry']   ?? null,
                    'is_active'    => $data['is_active']  ?? true,
                ]
            );
        }
    }
}
