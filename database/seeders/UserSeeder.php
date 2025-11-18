<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '0911111111',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Service Provider',
            'email' => 'provider@example.com',
            'phone' => '0922222222',
            'role' => 'service_provider',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'phone' => '0933333333',
            'role' => 'user',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
    }
}
