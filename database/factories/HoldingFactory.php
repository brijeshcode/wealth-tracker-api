<?php

namespace Database\Factories;

use App\Models\Holding;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HoldingFactory extends Factory
{
    protected $model = Holding::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'platform_id'      => Platform::factory(),
            'type'             => 'stock',
            'status'           => 'active',
            'principal_amount' => 0,
            'current_value'    => 0,
            'start_date'       => fake()->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
            'nickname'         => null,
            'notes'            => null,
        ];
    }
}
