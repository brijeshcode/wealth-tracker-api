<?php

namespace Database\Factories\Stocks;

use App\Models\Holding;
use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockHoldingFactory extends Factory
{
    protected $model = StockHolding::class;

    public function definition(): array
    {
        return [
            'holding_id'    => Holding::factory(),
            'user_id'       => User::factory(),
            'stock_id'      => Stock::factory(),
            'exchange'      => fake()->randomElement(['NSE', 'BSE']),
            'quantity'      => 0,
            'avg_buy_price' => 0,
        ];
    }
}
