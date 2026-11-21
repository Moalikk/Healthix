<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data banyak untuk load test. Dijalankan manual (tidak ikut migrate --seed):
 *   php artisan db:seed --class=LoadTestSeeder
 * Aman dijalankan ulang: akun loadtest lama dihapus dulu.
 */
class LoadTestSeeder extends Seeder
{
    private const CITIZENS = 100;
    private const PER_CITIZEN = 3;

    public function run(): void
    {
        $now = now();

        // Hapus data loadtest lama (appointments & notifications ikut terhapus lewat cascade/manual)
        $oldIds = DB::table('users')->where('email', 'like', 'loadtest%@quickcare.test')->pluck('id');
        DB::table('user_notifications')->whereIn('user_id', $oldIds)->delete();
        DB::table('appointments')->whereIn('user_id', $oldIds)->delete();
        DB::table('users')->whereIn('id', $oldIds)->delete();

        // Hash dihitung sekali supaya seeding cepat
        $password = Hash::make('password123');

        $users = [];
        for ($i = 1; $i <= self::CITIZENS; $i++) {
            $users[] = [
                'name'       => "Load Test Citizen $i",
                'email'      => "loadtest$i@quickcare.test",
                'password'   => $password,
                'role'       => 'citizen',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('users')->insert($users);

        $userIds = DB::table('users')->where('email', 'like', 'loadtest%@quickcare.test')->pluck('id');
        $departments = DB::table('departments')->pluck('id')->all();
        $complaints = ['demam dan batuk', 'kulit gatal', 'sakit gigi', 'mata merah', 'kontrol kehamilan', 'pusing'];

        $rows = [];
        foreach ($userIds as $n => $userId) {
            for ($k = 0; $k < self::PER_CITIZEN; $k++) {
                $rows[] = [
                    'user_id'          => $userId,
                    'department_id'    => $departments[($n + $k) % count($departments)],
                    'appointment_date' => $now->copy()->addDays($k % 3)->toDateString(),
                    'complaint'        => $complaints[($n + $k) % count($complaints)],
                    'priority'         => 'routine',
                    'status'           => 'pending',
                    'skip_count'       => 0,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('appointments')->insert($chunk);
        }

        $this->command?->info(self::CITIZENS . ' citizens, ' . count($rows) . ' pending appointments created.');
    }
}