<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        //seeders show same testing data in all three members
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('Admin12345'),
                'role' => 'admin',
                'must_change_password' => true,
                'preffered_language' => 'en',
                'is_active' => true,
            ]
        );
    }
}