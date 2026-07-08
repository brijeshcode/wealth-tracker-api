<?php

namespace App\Services\Stocks\Import;

use App\Models\Platform;
use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockTransaction;
use Carbon\Carbon;

class ImportValidator
{
    public function validate(array $rows, int $userId): array
    {
        $errors   = [];
        $warnings = [];
        $enriched = [];

        $symbols       = collect($rows)->pluck('symbol')->filter()->unique()->values()->all();
        $platformNames = collect($rows)->pluck('platform')->filter()->unique()->values()->all();

        $nseStocks = Stock::whereIn('nse_symbol', $symbols)->get()->keyBy('nse_symbol');
        $bseStocks = Stock::whereIn('bse_symbol', $symbols)->get()->keyBy('bse_symbol');
        $platforms = Platform::whereIn('name', $platformNames)->get()->keyBy('name');

        foreach ($rows as $index => $row) {
            $rowNum  = $index + 1;
            $rowErrs = $this->validateRow($row, $rowNum, $nseStocks, $bseStocks, $platforms);
            $errors  = array_merge($errors, $rowErrs);

            $stock    = $nseStocks[$row['symbol'] ?? ''] ?? $bseStocks[$row['symbol'] ?? ''] ?? null;
            $platform = $platforms[$row['platform'] ?? ''] ?? null;

            $enriched[] = array_merge($row, [
                'stock_id'    => $stock?->id,
                'platform_id' => $platform?->id,
            ]);
        }

        if (empty($errors)) {
            $this->checkFifo($enriched, $userId, $errors);
        }

        $this->checkDuplicates($enriched, $userId, $warnings);
        $this->checkIntraFileDuplicates($rows, $warnings);

        return ['errors' => $errors, 'warnings' => $warnings, 'enriched' => $enriched];
    }

    private function validateRow(
        array  $row,
        int    $rowNum,
        object $nseStocks,
        object $bseStocks,
        object $platforms,
    ): array {
        $errors = [];

        $date = trim($row['transaction_date'] ?? '');
        if ($date === '') {
            $errors[] = ['row' => $rowNum, 'column' => 'transaction_date', 'error' => 'Transaction date is required'];
        } elseif (!$this->isValidDatetime($date)) {
            $errors[] = ['row' => $rowNum, 'column' => 'transaction_date', 'error' => "Invalid date: {$date}"];
        } elseif (Carbon::parse($date)->isFuture()) {
            $errors[] = ['row' => $rowNum, 'column' => 'transaction_date', 'error' => 'Transaction date cannot be in the future'];
        }

        $symbol = strtoupper(trim($row['symbol'] ?? ''));
        if ($symbol === '') {
            $errors[] = ['row' => $rowNum, 'column' => 'symbol', 'error' => 'Symbol is required'];
        } elseif (!isset($nseStocks[$symbol]) && !isset($bseStocks[$symbol])) {
            $errors[] = ['row' => $rowNum, 'column' => 'symbol', 'error' => "{$symbol} not found in stock master"];
        }

        $exchange = strtoupper(trim($row['exchange'] ?? ''));
        if (!in_array($exchange, ['NSE', 'BSE'], true)) {
            $errors[] = ['row' => $rowNum, 'column' => 'exchange', 'error' => "Exchange must be NSE or BSE, got: {$exchange}"];
        }

        $type = strtolower(trim($row['type'] ?? ''));
        if (!in_array($type, ['buy', 'sell', 'dividend', 'bonus', 'split'], true)) {
            $errors[] = ['row' => $rowNum, 'column' => 'type', 'error' => "Invalid type: {$type}"];
        }

        if (in_array($type, ['buy', 'sell'], true)) {
            $qty = $row['quantity'] ?? null;
            if ($qty === null || $qty === '') {
                $errors[] = ['row' => $rowNum, 'column' => 'quantity', 'error' => 'Quantity is required for buy/sell'];
            } elseif ((float) $qty < 0.0001) {
                $errors[] = ['row' => $rowNum, 'column' => 'quantity', 'error' => 'Quantity must be at least 0.0001'];
            }
        }

        if (in_array($type, ['buy', 'sell'], true)) {
            $ppu = $row['price_per_unit'] ?? null;
            if ($ppu === null || $ppu === '') {
                $errors[] = ['row' => $rowNum, 'column' => 'price_per_unit', 'error' => 'Price per unit is required for buy/sell'];
            } elseif ((float) $ppu < 0) {
                $errors[] = ['row' => $rowNum, 'column' => 'price_per_unit', 'error' => 'Price per unit must be >= 0'];
            }
        }

        $platformName = trim($row['platform'] ?? '');
        if ($platformName === '') {
            $errors[] = ['row' => $rowNum, 'column' => 'platform', 'error' => 'Platform is required'];
        } elseif (!isset($platforms[$platformName])) {
            $errors[] = ['row' => $rowNum, 'column' => 'platform', 'error' => "Platform '{$platformName}' not found"];
        }

        return $errors;
    }

    private function checkFifo(array $enriched, int $userId, array &$errors): void
    {
        $annotated = [];
        foreach ($enriched as $i => $row) {
            $annotated[] = array_merge($row, ['_row_num' => $i + 1]);
        }

        usort($annotated, fn ($a, $b) => $a['transaction_date'] <=> $b['transaction_date']);

        $runningQty = [];

        foreach ($annotated as $row) {
            if (!in_array($row['type'], ['buy', 'sell'], true)) {
                continue;
            }

            $key = ($row['stock_id'] ?? 0) . '|' . $row['exchange'] . '|' . ($row['platform_id'] ?? 0);

            if (!array_key_exists($key, $runningQty)) {
                $runningQty[$key] = $this->existingQty($userId, $row['stock_id'], $row['exchange'], $row['platform_id']);
            }

            $qty = (float) ($row['quantity'] ?? 0);

            if ($row['type'] === 'buy') {
                $runningQty[$key] += $qty;
            } elseif ($row['type'] === 'sell') {
                if ($runningQty[$key] < $qty) {
                    $available = $runningQty[$key];
                    $errors[]  = [
                        'row'    => $row['_row_num'],
                        'column' => 'quantity',
                        'error'  => "Sell quantity {$qty} exceeds available {$available} for {$row['symbol']}",
                    ];
                } else {
                    $runningQty[$key] -= $qty;
                }
            }
        }
    }

    private function existingQty(int $userId, ?int $stockId, string $exchange, ?int $platformId): float
    {
        if ($stockId === null || $platformId === null) {
            return 0.0;
        }

        $holding = StockHolding::where('user_id', $userId)
            ->where('stock_id', $stockId)
            ->where('exchange', $exchange)
            ->whereHas('holding', fn ($q) => $q->where('platform_id', $platformId))
            ->first();

        return $holding ? (float) $holding->quantity : 0.0;
    }

    private function checkDuplicates(array $enriched, int $userId, array &$warnings): void
    {
        foreach ($enriched as $index => $row) {
            $rowNum = $index + 1;
            $ref    = $row['reference'] ?? null;

            if ($ref !== null && $ref !== '') {
                $exists = StockTransaction::whereHas(
                    'stockHolding',
                    fn ($q) => $q->where('user_id', $userId)
                )->where('reference', $ref)->exists();

                if ($exists) {
                    $warnings[] = [
                        'row'     => $rowNum,
                        'message' => "Definitive duplicate: a transaction with reference '{$ref}' already exists",
                    ];
                }
            } else {
                $date   = substr($row['transaction_date'], 0, 10);
                $exists = StockTransaction::whereHas(
                    'stockHolding',
                    fn ($q) => $q->where('user_id', $userId)
                        ->where('stock_id', $row['stock_id'])
                        ->where('exchange', $row['exchange'])
                        ->whereHas('holding', fn ($q2) => $q2->where('platform_id', $row['platform_id']))
                )->where('type', $row['type'])
                    ->where('quantity', $row['quantity'])
                    ->where('price_per_unit', $row['price_per_unit'])
                    ->whereDate('transaction_date', $date)
                    ->exists();

                if ($exists) {
                    $warnings[] = [
                        'row'     => $rowNum,
                        'message' => "Possible duplicate: {$row['symbol']} {$row['exchange']} {$row['type']} {$row['quantity']} @ {$row['price_per_unit']} on {$date} already exists",
                    ];
                }
            }
        }
    }

    private function checkIntraFileDuplicates(array $rows, array &$warnings): void
    {
        $seen = [];
        foreach ($rows as $index => $row) {
            $key = implode('|', [
                $row['transaction_date'] ?? '',
                $row['symbol']           ?? '',
                $row['exchange']         ?? '',
                $row['type']             ?? '',
                $row['quantity']         ?? '',
                $row['price_per_unit']   ?? '',
                $row['reference']        ?? '',
            ]);

            if (isset($seen[$key])) {
                $warnings[] = [
                    'row'     => $index + 1,
                    'message' => "Row {$seen[$key]} and row " . ($index + 1) . " appear to be identical within this file",
                ];
            } else {
                $seen[$key] = $index + 1;
            }
        }
    }

    private function isValidDatetime(string $value): bool
    {
        foreach (['Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                Carbon::createFromFormat($format, $value);
                return true;
            } catch (\Exception) {
            }
        }
        return false;
    }
}
