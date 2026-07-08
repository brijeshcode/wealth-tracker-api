<?php

use App\Models\Platform;
use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockTransaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function importCsv(array $rows, ?array $headers = null): UploadedFile
{
    $defaultHeaders = 'transaction_date,symbol,exchange,type,quantity,price_per_unit,platform,reference';
    $header         = $headers ? implode(',', $headers) : $defaultHeaders;
    $lines          = array_map(fn ($r) => implode(',', $r), $rows);
    $content        = implode("\n", array_merge([$header], $lines));
    return UploadedFile::fake()->createWithContent('import.csv', $content);
}

it('rejects unauthenticated preview request', function () {
    $this->postJson('/api/stock-transactions/import/preview')->assertStatus(401);
});

it('rejects unauthenticated confirm request', function () {
    $this->postJson('/api/stock-transactions/import/confirm')->assertStatus(401);
});

it('preview returns 422 with row-level errors for unknown symbol', function () {
    $user = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);
    Stock::factory()->create(['nse_symbol' => 'RELIANCE']);

    $file = importCsv([
        ['2024-01-15 10:00:00', 'UNKNOWN', 'NSE', 'buy', '10', '100.00', 'TestPlatform', ''],
    ]);

    $this->actingAs($user)
        ->postJson('/api/stock-transactions/import/preview', [
            'file'   => $file,
            'broker' => 'standard',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.0.column', 'symbol');
});

it('preview returns 200 with normalised rows for a valid file', function () {
    $user = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);
    Stock::factory()->create(['nse_symbol' => 'RELIANCE']);

    $file = importCsv([
        ['2024-01-15 10:00:00', 'RELIANCE', 'NSE', 'buy', '10', '2450.50', 'TestPlatform', 'TRD001'],
    ]);

    $this->actingAs($user)
        ->postJson('/api/stock-transactions/import/preview', [
            'file'   => $file,
            'broker' => 'standard',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.summary.total', 1)
        ->assertJsonPath('data.summary.buy', 1)
        ->assertJsonCount(1, 'data.rows');
});

it('preview returns warnings for duplicate reference', function () {
    $user  = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);
    $stock = Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    $holding  = StockHolding::factory()->create(['user_id' => $user->id, 'stock_id' => $stock->id]);

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

    $file = importCsv([
        ['2024-01-15 10:00:00', 'RELIANCE', 'NSE', 'buy', '10', '2450.50', 'TestPlatform', 'TRD001'],
    ]);

    $this->actingAs($user)
        ->postJson('/api/stock-transactions/import/preview', [
            'file'   => $file,
            'broker' => 'standard',
        ])
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.warnings');
});

it('confirm imports transactions and syncs holdings', function () {
    $user = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);
    Stock::factory()->create(['nse_symbol' => 'RELIANCE']);

    $rows = [[
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
    ]];

    $this->actingAs($user)
        ->postJson('/api/stock-transactions/import/confirm', [
            'rows'            => $rows,
            'ignore_warnings' => false,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.imported', 1)
        ->assertJsonPath('data.holdings_synced', 1);

    expect(StockTransaction::count())->toBe(1)
        ->and(StockHolding::first()->quantity)->toBe('10.0000');
});

it('confirm returns 409 when duplicates exist and ignore_warnings is false', function () {
    $user  = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);
    $stock = Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    $holding  = StockHolding::factory()->create(['user_id' => $user->id, 'stock_id' => $stock->id]);

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

    $rows = [[
        'transaction_date' => '2024-01-15 10:00:00',
        'symbol'           => 'RELIANCE',
        'exchange'         => 'NSE',
        'type'             => 'buy',
        'quantity'         => 10.0,
        'price_per_unit'   => 2450.50,
        'platform'         => 'TestPlatform',
        'reference'        => 'TRD001',
        'nickname'         => null,
        'notes'            => null,
    ]];

    $this->actingAs($user)
        ->postJson('/api/stock-transactions/import/confirm', [
            'rows'            => $rows,
            'ignore_warnings' => false,
        ])
        ->assertStatus(409);
});

it('confirm proceeds when ignore_warnings is true despite duplicates', function () {
    $user  = User::factory()->create();
    Platform::factory()->create(['name' => 'TestPlatform']);
    $stock = Stock::factory()->create(['nse_symbol' => 'RELIANCE']);
    $holding  = StockHolding::factory()->create(['user_id' => $user->id, 'stock_id' => $stock->id]);

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

    $rows = [[
        'transaction_date' => '2024-02-01 10:00:00',
        'symbol'           => 'RELIANCE',
        'exchange'         => 'NSE',
        'type'             => 'buy',
        'quantity'         => 5.0,
        'price_per_unit'   => 2500.00,
        'platform'         => 'TestPlatform',
        'reference'        => 'TRD001',
        'nickname'         => null,
        'notes'            => null,
    ]];

    $this->actingAs($user)
        ->postJson('/api/stock-transactions/import/confirm', [
            'rows'            => $rows,
            'ignore_warnings' => true,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.imported', 1);
});
