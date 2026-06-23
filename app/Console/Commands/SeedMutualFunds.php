<?php

namespace App\Console\Commands;

use App\Models\Mf\MutualFund;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedMutualFunds extends Command
{
    protected $signature   = 'mf:seed-funds';
    protected $description = 'Seed mutual_funds table from mfapi.in (upserts by amfi_code)';

    public function handle(): int
    {
        $this->info('Fetching fund list from mfapi.in...');

        $response = Http::timeout(60)->get('https://api.mfapi.in/mf');

        if (!$response->successful()) {
            $this->error('Failed to fetch: HTTP ' . $response->status());
            return self::FAILURE;
        }

        $funds = $response->json();
        $count = 0;

        foreach ($funds as $fund) {
            $code = (string) ($fund['schemeCode'] ?? '');
            $name = (string) ($fund['schemeName'] ?? '');

            if (!$code || !$name) {
                continue;
            }

            $plan = stripos($name, 'Direct') !== false ? 'direct' : 'regular';

            MutualFund::updateOrCreate(
                ['amfi_code' => $code],
                ['scheme_name' => $name, 'scheme_plan' => $plan, 'is_active' => true]
            );

            $count++;
        }

        $this->info("Done. Seeded {$count} mutual funds.");

        return self::SUCCESS;
    }
}
