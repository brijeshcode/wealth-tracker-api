<?php

namespace Database\Factories\Stocks;

use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockLot;
use App\Models\Stocks\StockTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockLotFactory extends Factory
{
    protected $model = StockLot::class;

    public function definition(): array
    {
        return [
            'stock_holding_id'   => StockHolding::factory(),
            'buy_transaction_id' => StockTransaction::factory(),
            'quantity_remaining' => fake()->randomFloat(4, 1, 100),
            'is_exhausted'       => false,
            'locked_until'       => null,
        ];
    }

    public function locked(): static
    {
        return $this->state(['locked_until' => now()->addMonths(6)->format('Y-m-d')]);
    }

    public function exhausted(): static
    {
        return $this->state(['quantity_remaining' => 0, 'is_exhausted' => true]);
    }
}
