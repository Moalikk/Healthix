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

        // Contoh dokter cuti (untuk mendemokan penolakan konfirmasi)
        $tomorrow = now()->addDay()->toDateString();
        $leaves = [
            ['dr. Bayu Pratama', 'Medical conference'],
            ['dr. Siti Aminah', 'Annual leave'],
        ];

        foreach ($leaves as [$doctorName, $reason]) {
            $doctorId = DB::table('doctors')->where('name', $doctorName)->value('id');
            if ($doctorId) {
                DB::table('doctor_leaves')->insert([
                    'doctor_id'  => $doctorId,
                    'leave_date' => $tomorrow,
                    'reason'     => $reason,
                ] + $ts);
            }
        }
    }
}