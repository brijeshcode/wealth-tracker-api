<?php

namespace App\Models\Stocks;

use Illuminate\Database\Eloquent\Model;

class StockPriceSyncLog extends Model
{
    protected $fillable = [
        'price_date',
        'status',
        'triggered_by',
        'stocks_updated',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'price_date'     => 'date',
            'stocks_updated' => 'integer',
        ];
    }
}
