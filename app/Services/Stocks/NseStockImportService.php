<?php

namespace App\Services\Stocks;

use App\Models\Stocks\Stock;
use League\Csv\Reader;

class NseStockImportService
{
    public function importFromPath(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        $csv    = Reader::createFromStream($handle);
        $csv->setHeaderOffset(0);

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        foreach ($csv->getRecords() as $raw) {
            $row = array_combine(array_map('trim', array_keys($raw)), array_values($raw));

            if (strtoupper(trim($row['SERIES'] ?? '')) !== 'EQ') {
                $skipped++;
                continue;
            }

            $isin   = trim($row['ISIN NUMBER'] ?? '');
            $symbol = strtoupper(trim($row['SYMBOL'] ?? ''));
            $name   = trim($row['NAME OF COMPANY'] ?? '');

            if (! $isin || ! $symbol) {
                $skipped++;
                continue;
            }

            $existing = Stock::withTrashed()->where('isin', $isin)->first();

            if ($existing) {
                $existing->update([
                    'company_name' => $name,
                    'nse_symbol'   => $symbol,
                    'bse_symbol'   => $existing->bse_symbol ?? $symbol,
                    'is_active'    => true,
                    'deleted_at'   => null,
                ]);
                $updated++;
            } else {
                Stock::create([
                    'isin'         => $isin,
                    'company_name' => $name,
                    'nse_symbol'   => $symbol,
                    'bse_symbol'   => $symbol,
                    'is_active'    => true,
                ]);
                $inserted++;
            }
        }

        fclose($handle);

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
    }

    public function importEtfFromPath(string $csvPath): array
    {
        // NSE ships this file in Windows-1252; convert to UTF-8 before parsing
        $content = mb_convert_encoding(file_get_contents($csvPath), 'UTF-8', 'Windows-1252');

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $csv = Reader::createFromStream($stream);
        $csv->setHeaderOffset(0);

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        foreach ($csv->getRecords() as $raw) {
            $row = array_combine(array_map('trim', array_keys($raw)), array_values($raw));

            $isin   = trim($row['ISINNumber'] ?? '');
            $symbol = strtoupper(trim($row['Symbol'] ?? ''));
            $name   = trim($row['SecurityName'] ?? '');

            if (! $isin || ! $symbol) {
                $skipped++;
                continue;
            }

            $existing = Stock::withTrashed()->where('isin', $isin)->first();

            if ($existing) {
                $existing->update([
                    'company_name' => $name,
                    'nse_symbol'   => $symbol,
                    'bse_symbol'   => $existing->bse_symbol ?? $symbol,
                    'sector'       => 'ETF',
                    'industry'     => trim($row['Underlying'] ?? 'ETF'),
                    'is_active'    => true,
                    'deleted_at'   => null,
                ]);
                $updated++;
            } else {
                Stock::create([
                    'isin'         => $isin,
                    'company_name' => $name,
                    'nse_symbol'   => $symbol,
                    'bse_symbol'   => $symbol,
                    'sector'       => 'ETF',
                    'industry'     => trim($row['Underlying'] ?? 'ETF'),
                    'is_active'    => true,
                ]);
                $inserted++;
            }
        }

        fclose($stream);

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
    }
}
