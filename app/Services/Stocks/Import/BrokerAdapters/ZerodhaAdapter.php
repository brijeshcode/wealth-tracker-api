<?php

namespace App\Services\Stocks\Import\BrokerAdapters;

use Carbon\Carbon;

class ZerodhaAdapter implements BrokerAdapterInterface
{
    private const TYPE_MAP = ['BUY' => 'buy', 'SELL' => 'sell'];

    public function normalize(array $rawRows): array
    {
        $rows = [];

        foreach ($rawRows as $row) {
            if (strtoupper(trim($row['Segment'] ?? '')) !== 'EQ') {
                continue;
            }

            $dateStr    = trim($row['Trade Date'] ?? '');
            $rawTime    = trim($row['Order Execution Time'] ?? '');
            // Order Execution Time may be a full datetime ("2022-12-15T13:59:05") or just a time
            if ($rawTime !== '' && (str_contains($rawTime, 'T') || str_contains($rawTime, ' '))) {
                $datetime = Carbon::parse($rawTime)->format('Y-m-d H:i:s');
            } elseif ($rawTime !== '') {
                $datetime = Carbon::createFromFormat('Y-m-d', $dateStr)->format('Y-m-d') . ' ' . $rawTime;
            } else {
                $datetime = Carbon::createFromFormat('Y-m-d', $dateStr)->format('Y-m-d') . ' 00:00:00';
            }

            $rawType = strtoupper(trim($row['Trade Type'] ?? ''));
            $type    = self::TYPE_MAP[$rawType] ?? strtolower($rawType);

            $rows[] = [
                'transaction_date' => $datetime,
                'symbol'           => strtoupper(trim($row['Symbol'] ?? '')),
                'exchange'         => strtoupper(trim($row['Exchange'] ?? '')),
                'type'             => $type,
                'quantity'         => (float) ($row['Quantity'] ?? 0),
                'price_per_unit'   => (float) ($row['Price'] ?? 0),
                'platform'         => 'zerodha',
                'reference'        => $row['Trade ID'] !== '' ? $row['Trade ID'] : null,
                'nickname'         => null,
                'notes'            => null,
            ];
        }

        return $rows;
    }
}
