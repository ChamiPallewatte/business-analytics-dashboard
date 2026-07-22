<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admins and Staff
        User::firstOrCreate(
            ['email' => 'admin@aiwa.com'],
            [
                'name' => 'Agency Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff1@aiwa.com'],
            [
                'name' => 'Sarah Manager',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff2@aiwa.com'],
            [
                'name' => 'Alex Account Executive',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );
    }
}

