<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin — can access /api/admin/* routes
        User::updateOrCreate(
            ['email' => 'admin@wealthtracker.dev'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('testtest'),
                'role'     => 'admin',
            ]
        );

        // Brijesh — primary dev user
        User::updateOrCreate(
            ['email' => 'brijesh@wealth.com'],
            [
                'name'     => 'Brijesh Chaturvedi',
                'password' => Hash::make('testtest'),
                'role'     => 'user',
            ]
        );

        // Generic test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'     => 'Test User',
                'password' => Hash::make('testtest'),
                'role'     => 'user',
            ]
        );
    }
}
