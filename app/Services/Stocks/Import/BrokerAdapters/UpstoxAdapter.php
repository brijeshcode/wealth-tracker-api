<?php

namespace App\Services\Stocks\Import\BrokerAdapters;

use Carbon\Carbon;

class UpstoxAdapter implements BrokerAdapterInterface
{
    private const TYPE_MAP = ['buy' => 'buy', 'sell' => 'sell', 'bonus' => 'bonus'];

    public function normalize(array $rawRows): array
    {
        $rows = [];

        foreach ($rawRows as $row) {
            if (strtoupper(trim($row['Segment'] ?? '')) !== 'EQ') {
                continue;
            }
            if (strtolower(trim($row['Instrument Type'] ?? '')) !== 'equity') {
                continue;
            }

            $dateStr = trim($row['Date'] ?? '');
            $date    = Carbon::createFromFormat('d-m-Y', $dateStr)->format('Y-m-d');

            $timeStr = trim($row['Trade Time'] ?? '');
            if ($timeStr === '') {
                $timeStr = '00:00:00';
            } elseif (substr_count($timeStr, ':') === 1) {
                $timeStr .= ':00';
            }

            $rawType = strtolower(trim($row['Side'] ?? ''));
            $type    = self::TYPE_MAP[$rawType] ?? $rawType;

            $priceStr = preg_replace('/[₹,\s]/', '', $row['Price'] ?? '0');
            $ref      = trim($row['Trade Num'] ?? '');

            $rows[] = [
                'transaction_date' => $date . ' ' . $timeStr,
                'symbol'           => strtoupper(trim($row['Company'] ?? '')),
                'exchange'         => strtoupper(trim($row['Exchange'] ?? '')),
                'type'             => $type,
                'quantity'         => (float) ($row['Quantity'] ?? 0),
                'price_per_unit'   => (float) $priceStr,
                'platform'         => 'upstox',
                'reference'        => $ref !== '' ? $ref : null,
                'nickname'         => null,
                'notes'            => null,
            ];
        }

        return $rows;
    }
}
