<?php

namespace App\Services\Stocks\Import\BrokerAdapters;

use Carbon\Carbon;

class GrowwAdapter implements BrokerAdapterInterface
{
    private const TYPE_MAP = ['buy' => 'buy', 'sell' => 'sell'];

    public function normalize(array $rawRows): array
    {
        $rows = [];

        foreach ($rawRows as $row) {
            if (strtolower(trim($row['Order status'] ?? '')) !== 'executed') {
                continue;
            }

            $datetimeStr = trim($row['Execution date and time'] ?? '');
            $datetime    = Carbon::parse($datetimeStr)->format('Y-m-d H:i:s');

            $qty      = (float) ($row['Quantity'] ?? 0);
            $rawValue = preg_replace('/[₹,\s]/', '', $row['Value'] ?? '0');
            $value    = (float) $rawValue;
            $ppu      = $qty > 0 ? round($value / $qty, 4) : 0;

            $rawType = strtolower(trim($row['Type'] ?? ''));
            $type    = self::TYPE_MAP[$rawType] ?? $rawType;

            $ref = trim($row['Exchange Order Id'] ?? '');

            $rows[] = [
                'transaction_date' => $datetime,
                'symbol'           => strtoupper(trim($row['Symbol'] ?? '')),
                'exchange'         => strtoupper(trim($row['Exchange'] ?? '')),
                'type'             => $type,
                'quantity'         => $qty,
                'price_per_unit'   => $ppu,
                'platform'         => 'groww',
                'reference'        => $ref !== '' ? $ref : null,
                'nickname'         => null,
                'notes'            => null,
            ];
        }

        return $rows;
    }
}
