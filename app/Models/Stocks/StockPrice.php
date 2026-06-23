<?php

namespace App\Models\Stocks;

use App\Traits\Authorable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockPrice extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected $fillable = [
        'stock_id',
        'price_date',
        'close_price',
        'volume',
    ];

    protected function casts(): array
    {
        return [
            'price_date'  => 'date',
            'close_price' => 'decimal:4',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
