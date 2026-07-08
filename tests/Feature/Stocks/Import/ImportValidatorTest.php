<?php

use App\Models\Platform;
use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockTransaction;
use App\Models\User;
use App\Services\Stocks\Import\ImportValidator;

function validRow(array $overrides = []): array
{
    return array_merge([
        'transaction_date' => '2024-01-15 10:00:00',
        'symbol'           => 'RELIANCE',
        'exchange'         => 'NSE',
        'type'             => 'buy',
        'quantity'         => 10.0,
        'price_per_unit'   => 2450.50,
        'platform'         => 'TestPlatform',
        'reference'        => null,
        'nickname'         => null,
        'notes'            => null,
    ], $overrides);
}

it('returns no errors for a valid buy row', function () {
    $user     = User::factory()->create();
    $stock    = Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    $platform = Platform::factory()->create(['name' => 'TestPlatform']);

    $result = (new ImportValidator())->validate([validRow()], $user->id);

    expect($result['errors'])->toBeEmpty()
        ->and($result['enriched'][0]['stock_id'])->toBe($stock->id)
        ->and($result['enriched'][0]['platform_id'])->toBe($platform->id);
});

it('errors when symbol does not exist in stock master', function () {
    $user     = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);

    $result = (new ImportValidator())->validate([validRow(['symbol' => 'UNKNOWN'])], $user->id);

    expect($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['column'])->toBe('symbol')
        ->and($result['errors'][0]['row'])->toBe(1);
});

it('errors when platform does not exist', function () {
    $user  = User::factory()->create();
    Stock::factory()->create(['nse_symbol' => 'RELIANCE']);

    $result = (new ImportValidator())->validate([validRow(['platform' => 'UnknownBroker'])], $user->id);

    expect($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['column'])->toBe('platform');
});

it('errors when sell quantity exceeds available quantity in cross-row FIFO check', function () {
    $user     = User::factory()->create();
    Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    Platform::factory()->create(['name' => 'TestPlatform']);

    $rows = [
        validRow(['type' => 'buy',  'quantity' => 5.0,  'transaction_date' => '2024-01-15 10:00:00']),
        validRow(['type' => 'sell', 'quantity' => 10.0, 'transaction_date' => '2024-01-16 10:00:00']),
    ];

    $result = (new ImportValidator())->validate($rows, $user->id);

    expect($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['column'])->toBe('quantity');
});

it('warns on definitive duplicate when reference matches an existing transaction', function () {
    $user     = User::factory()->create();
    $stock    = Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    $platform = Platform::factory()->create(['name' => 'TestPlatform']);

    $holding = StockHolding::factory()->create(['user_id' => $user->id, 'stock_id' => $stock->id]);
    StockTransaction::factory()->create([
        'stock_holding_id' => $holding->id,
        'reference'        => 'TRD001',
        'type'             => 'buy',
        'quantity'         => 10,
        'price_per_unit'   => 2450.50,
        'transaction_date' => '2024-01-15 10:00:00',
        'amount'           => 24505,
        'source'           => 'manual',
    ]);

    $result = (new ImportValidator())->validate([validRow(['reference' => 'TRD001'])], $user->id);

    expect($result['errors'])->toBeEmpty()
        ->and($result['warnings'])->toHaveCount(1)
        ->and($result['warnings'][0]['row'])->toBe(1);
});

it('warns on within-file duplicate rows', function () {
    $user     = User::factory()->create();
    Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    Platform::factory()->create(['name' => 'TestPlatform']);

    $result = (new ImportValidator())->validate([validRow(), validRow()], $user->id);

    expect($result['errors'])->toBeEmpty()
        ->and($result['warnings'])->not->toBeEmpty();
});
