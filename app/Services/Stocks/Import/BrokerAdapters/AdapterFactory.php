<?php

namespace App\Services\Stocks\Import\BrokerAdapters;

class AdapterFactory
{
    public static function make(string $broker): BrokerAdapterInterface
    {
        return match (strtolower($broker)) {
            'zerodha' => new ZerodhaAdapter(),
            'groww'   => new GrowwAdapter(),
            'upstox'  => new UpstoxAdapter(),
            default   => new StandardAdapter(),
        };
    }
}
