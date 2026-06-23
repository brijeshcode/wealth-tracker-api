<?php

namespace App\Models\Stocks;

use App\Models\Holding;
use App\Traits\Authorable;
use App\Traits\BelongsToUser;
use Database\Factories\Stocks\StockHoldingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockHolding extends Model
{
    use Authorable, BelongsToUser, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return StockHoldingFactory::new();
    }

    protected $fillable = [
        'holding_id',
        'user_id',
        'stock_id',
        'exchange',
        'quantity',
        'avg_buy_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity'      => 'decimal:4',
            'avg_buy_price' => 'decimal:4',
        ];
    }

    public function holding(): BelongsTo
    {
        return $this->belongsTo(Holding::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }
}
