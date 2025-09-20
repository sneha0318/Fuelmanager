<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UltraSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager',
                'mobile' => '9999999988',
                'user_type' => 'ultra_super_admin',
                'password' => Hash::make('123456789'),
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );
    }
}
