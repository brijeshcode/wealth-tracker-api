<?php

use App\Services\Stocks\Import\FileParser;
use Illuminate\Http\UploadedFile;

it('parses a valid CSV file into row arrays', function () {
    $csv = implode("\n", [
        'transaction_date,symbol,exchange,type,quantity,price_per_unit,platform',
        '2024-01-15 10:00:00,RELIANCE,NSE,buy,10,2450.50,TestPlatform',
        '2024-02-01 11:30:00,TCS,NSE,sell,5,3500.00,TestPlatform',
    ]);

    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);
    $rows = (new FileParser())->parse($file);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['symbol'])->toBe('RELIANCE')
        ->and($rows[1]['type'])->toBe('sell');
});

it('throws on unsupported file extension', function () {
    $file = UploadedFile::fake()->createWithContent('import.pdf', 'data');
    (new FileParser())->parse($file);
})->throws(\InvalidArgumentException::class);

it('skips empty rows when parsing CSV', function () {
    $csv = implode("\n", [
        'transaction_date,symbol,exchange,type,quantity,price_per_unit,platform',
        '2024-01-15 10:00:00,RELIANCE,NSE,buy,10,2450.50,TestPlatform',
        ',,,,,',
        '2024-02-01 11:30:00,TCS,NSE,sell,5,3500.00,TestPlatform',
    ]);

    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);
    $rows = (new FileParser())->parse($file);

    expect($rows)->toHaveCount(2);
});
