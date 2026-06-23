<?php

namespace App\Models\Stocks;

use App\Traits\Authorable;
use Database\Factories\Stocks\StockLotFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockLot extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return StockLotFactory::new();
    }

    protected $fillable = [
        'stock_holding_id',
        'buy_transaction_id',
        'quantity_remaining',
        'is_exhausted',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'quantity_remaining' => 'decimal:4',
            'is_exhausted'       => 'boolean',
            'locked_until'       => 'date',
        ];
    }

    public function stockHolding(): BelongsTo
    {
        return $this->belongsTo(StockHolding::class);
    }

    public function buyTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'buy_transaction_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
