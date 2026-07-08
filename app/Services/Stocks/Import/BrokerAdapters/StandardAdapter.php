<?php

namespace App\Services\Stocks\Import\BrokerAdapters;

class StandardAdapter implements BrokerAdapterInterface
{
    public function normalize(array $rawRows): array
    {
        return array_map(function (array $row) {
            $date = trim($row['transaction_date'] ?? $row['date'] ?? '');
            if (strlen($date) === 10) {
                $date .= ' 00:00:00';
            }

            return [
                'transaction_date' => $date,
                'symbol'           => strtoupper(trim($row['symbol'] ?? '')),
                'exchange'         => strtoupper(trim($row['exchange'] ?? '')),
                'type'             => strtolower(trim($row['type'] ?? '')),
                'quantity'         => isset($row['quantity']) && $row['quantity'] !== '' ? (float) $row['quantity'] : null,
                'price_per_unit'   => isset($row['price_per_unit']) && $row['price_per_unit'] !== '' ? (float) $row['price_per_unit'] : null,
                'platform'         => trim($row['platform'] ?? ''),
                'reference'        => isset($row['reference']) && $row['reference'] !== '' ? $row['reference'] : null,
                'nickname'         => isset($row['nickname']) && $row['nickname'] !== '' ? $row['nickname'] : null,
                'notes'            => isset($row['notes']) && $row['notes'] !== '' ? $row['notes'] : null,
            ];
        }, $rawRows);
    }
}
