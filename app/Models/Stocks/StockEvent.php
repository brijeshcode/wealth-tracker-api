<?php

namespace App\Models\Stocks;

use App\Traits\Authorable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockEvent extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected $fillable = [
        'stock_id',
        'event_type',
        'event_date',
        'ratio_numerator',
        'ratio_denominator',
        'notes',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
