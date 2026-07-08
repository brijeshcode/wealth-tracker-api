<?php

namespace App\Services\Stocks\Import\BrokerAdapters;

interface BrokerAdapterInterface
{
    /**
     * @param  array<array<string, string>>  $rawRows
     * @return array<array<string, mixed>>
     */
    public function normalize(array $rawRows): array;
}
