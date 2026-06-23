<?php

namespace Database\Factories\Stocks;

use App\Models\Stocks\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'isin'         => 'INE' . strtoupper(fake()->unique()->bothify('???######')),
            'company_name' => fake()->company() . ' Ltd',
            'nse_symbol'   => strtoupper(fake()->unique()->lexify('????')),
            'bse_symbol'   => strtoupper(fake()->unique()->lexify('????')),
            'bse_code'     => fake()->unique()->numerify('######'),
            'sector'       => fake()->randomElement(['IT', 'Banking', 'Pharma', 'Auto']),
            'industry'     => fake()->randomElement(['Software', 'Private Banks', 'Generics']),
            'is_active'    => true,
        ];
    }
}
