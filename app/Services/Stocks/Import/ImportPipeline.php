<?php

namespace App\Services\Stocks\Import;

use App\Services\Stocks\FifoService;
use App\Services\Stocks\HoldingsCalculator;
use App\Services\Stocks\Import\BrokerAdapters\AdapterFactory;
use App\Services\Stocks\StockHoldingResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportPipeline
{
    public function __construct(
        private FileParser           $parser,
        private ImportValidator      $validator,
        private StockHoldingResolver $resolver,
        private FifoService          $fifo,
        private HoldingsCalculator   $calculator,
    ) {}

    public function preview(UploadedFile $file, string $broker, int $userId): array
    {
        $rawRows    = $this->parser->parse($file);
        $adapter    = AdapterFactory::make($broker);
        $normalized = $adapter->normalize($rawRows);

        $result = $this->validator->validate($normalized, $userId);

        if (!empty($result['errors'])) {
            return ['errors' => $result['errors']];
        }

        $types   = collect($normalized)->groupBy('type');
        $summary = [
            'total' => count($normalized),
            'buy'   => $types->get('buy', collect())->count(),
            'sell'  => $types->get('sell', collect())->count(),
        ];

        return [
            'summary'  => $summary,
            'warnings' => $result['warnings'],
            'rows'     => $normalized,
        ];
    }

    public function confirm(array $rows, bool $ignoreWarnings, int $userId): array
    {
        $result = $this->validator->validate($rows, $userId);

        if (!empty($result['errors'])) {
            return ['_type' => 'validation_error', 'errors' => $result['errors']];
        }

        if (!$ignoreWarnings && !empty($result['warnings'])) {
            return ['_type' => 'duplicate_warning', 'warnings' => $result['warnings']];
        }

        $enriched = $result['enriched'];

        usort($enriched, fn ($a, $b) => $a['transaction_date'] <=> $b['transaction_date']);

        $syncedHoldings = [];
        $imported       = 0;

        DB::transaction(function () use ($enriched, $userId, &$syncedHoldings, &$imported) {
            foreach ($enriched as $row) {
                $holding = $this->resolver->resolve(
                    userId:          $userId,
                    stockId:         $row['stock_id'],
                    exchange:        $row['exchange'],
                    platformId:      $row['platform_id'],
                    transactionDate: $row['transaction_date'],
                    nickname:        $row['nickname'],
                    notes:           $row['notes'],
                );

                $qty = $row['quantity'] ?? null;
                $ppu = $row['price_per_unit'] ?? null;

                $txn = $holding->transactions()->create([
                    'type'             => $row['type'],
                    'quantity'         => $qty,
                    'price_per_unit'   => $ppu,
                    'amount'           => ($qty && $ppu) ? $qty * $ppu : 0,
                    'transaction_date' => $row['transaction_date'],
                    'source'           => 'csv_import',
                    'reference'        => $row['reference'],
                ]);

                if ($txn->type === 'sell') {
                    $this->fifo->consumeLots($holding, (float) $txn->quantity);
                } elseif ($txn->type === 'buy') {
                    $this->fifo->createLot($txn);
                }

                $syncedHoldings[$holding->id] = $holding;
                $imported++;
            }

            foreach ($syncedHoldings as $holding) {
                $this->calculator->sync($holding);
            }
        });

        return [
            '_type'           => 'success',
            'imported'        => $imported,
            'holdings_synced' => count($syncedHoldings),
        ];
    }
}
