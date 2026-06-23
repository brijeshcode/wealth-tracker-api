<?php

namespace App\Models\Stocks;

use App\Traits\Authorable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMeta extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected $fillable = ['stock_id', 'key', 'value'];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
