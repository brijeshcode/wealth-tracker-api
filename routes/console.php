<?php

use App\Services\Stocks\StockPriceSyncService;
use App\Services\Stocks\StockPriceRollupService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily closing price sync — weekdays at 16:05 IST (10:35 UTC)
Schedule::call(function () {
    app(StockPriceSyncService::class)->sync(Carbon::today(), 'scheduler');
})->weekdays()->dailyAt('10:35')->timezone('UTC')->name('stock-price-sync');

// Monthly: roll up daily→weekly for data >1 year, clean success logs >1 month
Schedule::call(function () {
    app(StockPriceRollupService::class)->rollup();
    app(StockPriceRollupService::class)->cleanSuccessLogs();
})->monthlyOn(1, '02:00')->name('stock-price-rollup-and-cleanup');
