<?php

namespace Database\Factories;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformFactory extends Factory
{
    protected $model = Platform::class;

    public function definition(): array
    {
        return [
            'name'                  => fake()->unique()->slug(2),
            'display_name'          => fake()->company(),
            'type'                  => fake()->randomElement(['broker', 'bank', 'app']),
            'supported_asset_types' => ['stock', 'mf'],
        ];
    }
}
