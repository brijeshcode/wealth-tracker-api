<?php

use App\Services\Stocks\Import\BrokerAdapters\AdapterFactory;
use App\Services\Stocks\Import\BrokerAdapters\GrowwAdapter;
use App\Services\Stocks\Import\BrokerAdapters\StandardAdapter;
use App\Services\Stocks\Import\BrokerAdapters\UpstoxAdapter;
use App\Services\Stocks\Import\BrokerAdapters\ZerodhaAdapter;

// ---------------------------------------------------------------------------
// AdapterFactory
// ---------------------------------------------------------------------------

it('resolves the correct adapter for each broker string', function (string $broker, string $class) {
    expect(AdapterFactory::make($broker))->toBeInstanceOf($class);
})->with([
    ['standard', StandardAdapter::class],
    ['zerodha',  ZerodhaAdapter::class],
    ['groww',    GrowwAdapter::class],
    ['upstox',   UpstoxAdapter::class],
    ['ZERODHA',  ZerodhaAdapter::class],
]);

// ---------------------------------------------------------------------------
// StandardAdapter
// ---------------------------------------------------------------------------

it('normalises standard rows with date-only input', function () {
    $rows = (new StandardAdapter())->normalize([[
        'transaction_date' => '2024-01-15',
        'symbol'           => 'reliance',
        'exchange'         => 'nse',
        'type'             => 'BUY',
        'quantity'         => '10',
        'price_per_unit'   => '2450.50',
        'platform'         => 'TestPlatform',
        'reference'        => null,
        'nickname'         => null,
        'notes'            => null,
    ]]);

    expect($rows[0]['transaction_date'])->toBe('2024-01-15 00:00:00')
        ->and($rows[0]['symbol'])->toBe('RELIANCE')
        ->and($rows[0]['exchange'])->toBe('NSE')
        ->and($rows[0]['type'])->toBe('buy')
        ->and($rows[0]['quantity'])->toBe(10.0)
        ->and($rows[0]['price_per_unit'])->toBe(2450.50);
});

// ---------------------------------------------------------------------------
// ZerodhaAdapter
// ---------------------------------------------------------------------------

it('normalises zerodha rows and filters non-EQ segments', function () {
    $raw = [
        [
            'Symbol'               => 'RELIANCE',
            'ISIN'                 => 'INE002A01018',
            'Trade Date'           => '2024-01-15',
            'Exchange'             => 'NSE',
            'Segment'              => 'EQ',
            'Series'               => 'EQ',
            'Trade Type'           => 'BUY',
            'Auction'              => '',
            'Quantity'             => '10',
            'Price'                => '2450.50',
            'Trade ID'             => 'TRD001',
            'Order ID'             => 'ORD001',
            'Order Execution Time' => '10:30:00',
        ],
        [
            'Symbol'               => 'NIFTY',
            'ISIN'                 => '',
            'Trade Date'           => '2024-01-15',
            'Exchange'             => 'NSE',
            'Segment'              => 'FNO',
            'Series'               => 'FUT',
            'Trade Type'           => 'BUY',
            'Auction'              => '',
            'Quantity'             => '50',
            'Price'                => '100',
            'Trade ID'             => 'TRD002',
            'Order ID'             => 'ORD002',
            'Order Execution Time' => '11:00:00',
        ],
    ];

    $rows = (new ZerodhaAdapter())->normalize($raw);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['transaction_date'])->toBe('2024-01-15 10:30:00')
        ->and($rows[0]['symbol'])->toBe('RELIANCE')
        ->and($rows[0]['type'])->toBe('buy')
        ->and($rows[0]['reference'])->toBe('TRD001')
        ->and($rows[0]['platform'])->toBe('zerodha');
});

// ---------------------------------------------------------------------------
// GrowwAdapter
// ---------------------------------------------------------------------------

it('normalises groww rows, filters non-executed, and derives price from value/qty', function () {
    $raw = [
        [
            'Stock name'              => 'RELIANCE INDUSTRIES',
            'Symbol'                  => 'RELIANCE',
            'ISIN'                    => 'INE002A01018',
            'Type'                    => 'BUY',
            'Quantity'                => '10',
            'Value'                   => '₹24,505.00',
            'Exchange'                => 'NSE',
            'Exchange Order Id'       => 'EXORD001',
            'Execution date and time' => '2024-01-15 10:30:00',
            'Order status'            => 'Executed',
        ],
        [
            'Stock name'              => 'TCS',
            'Symbol'                  => 'TCS',
            'ISIN'                    => 'INE467B01029',
            'Type'                    => 'BUY',
            'Quantity'                => '5',
            'Value'                   => '₹17,500.00',
            'Exchange'                => 'NSE',
            'Exchange Order Id'       => 'EXORD002',
            'Execution date and time' => '2024-01-16 09:00:00',
            'Order status'            => 'Pending',
        ],
    ];

    $rows = (new GrowwAdapter())->normalize($raw);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['symbol'])->toBe('RELIANCE')
        ->and($rows[0]['price_per_unit'])->toBe(2450.5)
        ->and($rows[0]['transaction_date'])->toBe('2024-01-15 10:30:00')
        ->and($rows[0]['reference'])->toBe('EXORD001')
        ->and($rows[0]['platform'])->toBe('groww');
});

// ---------------------------------------------------------------------------
// UpstoxAdapter
// ---------------------------------------------------------------------------

it('normalises upstox rows, filters non-EQ, strips rupee symbol, and handles bonus type', function () {
    $raw = [
        [
            'Date'            => '09-10-2025',
            'Company'         => 'RELIANCE',
            'Amount'          => '₹84,870.00',
            'Exchange'        => 'NSE',
            'Segment'         => 'EQ',
            'Scrip Code'      => '543320',
            'Instrument Type' => 'Equity',
            'Strike Price'    => '0',
            'Expiry'          => '0',
            'Trade Num'       => '208010602',
            'Trade Time'      => '14:55:22',
            'Side'            => 'Sell',
            'Quantity'        => '246',
            'Price'           => '₹345.00',
        ],
        [
            'Date'            => '16-07-2025',
            'Company'         => 'ASHOKLEY',
            'Amount'          => '₹0.00',
            'Exchange'        => 'NSE',
            'Segment'         => 'EQ',
            'Scrip Code'      => '500477',
            'Instrument Type' => 'Equity',
            'Strike Price'    => '0',
            'Expiry'          => '0',
            'Trade Num'       => '',
            'Trade Time'      => '',
            'Side'            => 'BONUS',
            'Quantity'        => '122',
            'Price'           => '₹0.00',
        ],
        [
            'Date'            => '09-10-2025',
            'Company'         => 'NIFTY',
            'Amount'          => '₹10,000.00',
            'Exchange'        => 'NSE',
            'Segment'         => 'FNO',
            'Scrip Code'      => '999',
            'Instrument Type' => 'Futures',
            'Strike Price'    => '0',
            'Expiry'          => '30-10-2025',
            'Trade Num'       => '111',
            'Trade Time'      => '10:00:00',
            'Side'            => 'Buy',
            'Quantity'        => '50',
            'Price'           => '₹200.00',
        ],
    ];

    $rows = (new UpstoxAdapter())->normalize($raw);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['transaction_date'])->toBe('2025-10-09 14:55:22')
        ->and($rows[0]['symbol'])->toBe('RELIANCE')
        ->and($rows[0]['type'])->toBe('sell')
        ->and($rows[0]['price_per_unit'])->toBe(345.0)
        ->and($rows[0]['reference'])->toBe('208010602')
        ->and($rows[1]['type'])->toBe('bonus')
        ->and($rows[1]['transaction_date'])->toBe('2025-07-16 00:00:00')
        ->and($rows[1]['platform'])->toBe('upstox');
});
