<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin account
        User::updateOrCreate(
            ['email' => 'admin@wanderly.test'],
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Demo Traveler account (User A - Leader)
        User::updateOrCreate(
            ['email' => 'demo@wanderly.test'],
            [
                'name' => 'Demo Traveler',
                'username' => 'demotraveler',
                'password' => Hash::make('password'),
                'role' => 'traveler',
                'email_verified_at' => now(),
            ]
        );

        // 3. Demo Member account (User B - Member / Editor)
        User::updateOrCreate(
            ['email' => 'member@wanderly.test'],
            [
                'name' => 'Member Traveler',
                'username' => 'membertraveler',
                'password' => Hash::make('password'),
                'role' => 'traveler',
                'email_verified_at' => now(),
            ]
        );
    }
}

