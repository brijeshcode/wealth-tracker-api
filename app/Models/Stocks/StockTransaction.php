<?php

namespace App\Models\Stocks;

use App\Traits\Authorable;
use Database\Factories\Stocks\StockTransactionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransaction extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return StockTransactionFactory::new();
    }

    protected $fillable = [
        'stock_holding_id',
        'type',
        'quantity',
        'price_per_unit',
        'amount',
        'transaction_date',
        'source',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'decimal:4',
            'price_per_unit'   => 'decimal:4',
            'amount'           => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function stockHolding(): BelongsTo
    {
        return $this->belongsTo(StockHolding::class);
    }

    public function lot(): HasOne
    {
        return $this->hasOne(StockLot::class, 'buy_transaction_id');
    }
}
