<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@wealthtracker.dev',
            'role'  => 'admin',
        ]);

        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
            'role'  => 'user',
        ]);

        $this->call([
            PlatformSeeder::class,
            StockSeeder::class,
        ]);
    }
}
