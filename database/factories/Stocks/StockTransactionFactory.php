<?php

namespace Database\Factories\Stocks;

use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransactionFactory extends Factory
{
    protected $model = StockTransaction::class;

    public function definition(): array
    {
        $qty   = fake()->randomFloat(4, 1, 100);
        $price = fake()->randomFloat(4, 10, 5000);

        return [
            'stock_holding_id' => StockHolding::factory(),
            'type'             => 'buy',
            'quantity'         => $qty,
            'price_per_unit'   => $price,
            'amount'           => round($qty * $price, 2),
            'transaction_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'source'           => 'manual',
            'reference'        => null,
        ];
    }

    public function buy(): static
    {
        return $this->state(['type' => 'buy']);
    }

    public function sell(): static
    {
        return $this->state(['type' => 'sell']);
    }
}
