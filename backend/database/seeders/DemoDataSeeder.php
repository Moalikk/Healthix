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

        // Dokter tambahan. department_id mengikuti DatabaseSeeder:
        // 1 General Medicine, 2 Dermatology, 3 Dental, 4 Eye
        DB::table('doctors')->insert([
            ['department_id' => 1, 'name' => 'dr. Rina Wulandari', 'specialization' => 'General Practitioner'] + $ts,
            ['department_id' => 2, 'name' => 'dr. Bayu Pratama', 'specialization' => 'Dermatologist'] + $ts,
            ['department_id' => 3, 'name' => 'drg. Maya Lestari', 'specialization' => 'Dentist'] + $ts,
            ['department_id' => 4, 'name' => 'dr. Hendra Kusuma', 'specialization' => 'Ophthalmologist'] + $ts,
            ['department_id' => 4, 'name' => 'dr. Siti Aminah', 'specialization' => 'Ophthalmologist'] + $ts,
        ]);

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