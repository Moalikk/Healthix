<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $ts = ['created_at' => now(), 'updated_at' => now()];
        // Akun demo (password hanya untuk demo)
        $users = [
            ['name' => 'Hospital Staff', 'email' => 'staff@quickcare.test', 'role' => 'staff'],
            ['name' => 'Demo Citizen', 'email' => 'citizen@quickcare.test', 'role' => 'citizen'],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password123'),
                ] + $ts
            );
        }
    }
}