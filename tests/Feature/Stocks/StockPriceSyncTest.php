<?php

use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockPriceSyncLog;
use App\Models\Stocks\StockPrice;
use App\Services\Stocks\StockPriceSyncService;
use App\Services\Stocks\StockPriceRollupService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

it('skips weekends and does not call Yahoo Finance', function () {
    Http::fake();

    $saturday = Carbon::parse('2026-07-04'); // Saturday
    $result   = app(StockPriceSyncService::class)->sync($saturday, 'api');

    expect($result['status'])->toBe('skipped');
    Http::assertNothingSent();
});

it('returns failed when Yahoo Finance returns non-200', function () {
    $stock = Stock::factory()->create(['nse_symbol' => 'RELIANCE', 'is_active' => true]);
    StockHolding::factory()->create(['stock_id' => $stock->id, 'quantity' => 10]);

    Http::fake(['*' => Http::response('', 503)]);

    $monday = Carbon::parse('2026-07-07');
    $result = app(StockPriceSyncService::class)->sync($monday, 'api');

    expect($result['status'])->toBe('failed');
    expect($result['message'])->toContain('503');
});

it('rolls up daily prices older than 1 year to weekly', function () {
    $stock = Stock::factory()->create(['isin' => 'TEST123', 'nse_symbol' => 'TEST', 'is_active' => true]);

    // Insert 5 daily prices from 2 years ago (same week)
    $monday = Carbon::now()->subYears(2)->startOfWeek();
    foreach (range(0, 4) as $day) {
        StockPrice::create([
            'stock_id'    => $stock->id,
            'price_date'  => $monday->copy()->addDays($day)->toDateString(),
            'period'      => 'daily',
            'close_price' => 100 + $day,
            'volume'      => 1000,
        ]);
    }

    $rolled = app(StockPriceRollupService::class)->rollup();

    // 5 daily → 1 weekly
    expect(StockPrice::where('stock_id', $stock->id)->where('period', 'daily')->count())->toBe(0);
    expect(StockPrice::where('stock_id', $stock->id)->where('period', 'weekly')->count())->toBe(1);
    // Weekly record should have Friday's close price (104)
    expect((float) StockPrice::where('stock_id', $stock->id)->where('period', 'weekly')->value('close_price'))->toBe(104.0);
    expect($rolled)->toBe(1);
});

it('cleans success logs older than 1 month but keeps failed logs', function () {
    // Old success log — should be deleted
    StockPriceSyncLog::create([
        'price_date'     => Carbon::now()->subMonths(2)->toDateString(),
        'status'         => 'success',
        'triggered_by'   => 'scheduler',
        'stocks_updated' => 10,
    ]);

    // Old failed log — must be kept
    StockPriceSyncLog::create([
        'price_date'     => Carbon::now()->subMonths(2)->toDateString(),
        'status'         => 'failed',
        'triggered_by'   => 'api',
        'stocks_updated' => 0,
        'message'        => 'NSE was down',
    ]);

    $deleted = app(StockPriceRollupService::class)->cleanSuccessLogs();

    expect($deleted)->toBe(1);
    expect(StockPriceSyncLog::count())->toBe(1);
    expect(StockPriceSyncLog::first()->status)->toBe('failed');
});
