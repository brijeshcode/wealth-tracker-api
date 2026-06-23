<?php

namespace App\Models;

use App\Models\Stocks\StockHolding;
use App\Traits\Authorable;
use App\Traits\BelongsToUser;
use Database\Factories\HoldingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holding extends Model
{
    use Authorable, BelongsToUser, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return HoldingFactory::new();
    }

    protected $fillable = [
        'user_id',
        'platform_id',
        'type',
        'status',
        'principal_amount',
        'current_value',
        'start_date',
        'end_date',
        'nickname',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'current_value'    => 'decimal:2',
            'start_date'       => 'date',
            'end_date'         => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function stockHolding(): HasOne
    {
        return $this->hasOne(StockHolding::class);
    }
}
