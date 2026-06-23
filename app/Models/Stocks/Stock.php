<?php

namespace App\Models\Stocks;

use App\Traits\Authorable;
use Database\Factories\Stocks\StockFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return StockFactory::new();
    }

    protected $fillable = [
        'isin',
        'company_name',
        'nse_symbol',
        'bse_symbol',
        'bse_code',
        'sector',
        'industry',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function meta(): HasMany
    {
        return $this->hasMany(StockMeta::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(StockEvent::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(StockPrice::class)->orderByDesc('price_date');
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(StockPrice::class)->latestOfMany('price_date');
    }
}
