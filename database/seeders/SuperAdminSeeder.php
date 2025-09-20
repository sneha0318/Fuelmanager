<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'mobile' => '9999999999',
                'user_type' => 'super_admin',
                'password' => Hash::make('superadmin123'),
                'email_verified_at' => now()
            ]
        );
    }
} 