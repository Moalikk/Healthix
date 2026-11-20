<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Notifier
{
    // Simpan satu notifikasi untuk seorang user
    public static function send(int $userId, ?int $appointmentId, string $title, string $message): void
    {
        DB::table('user_notifications')->insert([
            'user_id'        => $userId,
            'appointment_id' => $appointmentId,
            'title'          => $title,
            'message'        => $message,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    // Contoh: "Dermatology · 25 Nov 2026"
    public static function label(object $appointment): string
    {
        $department = DB::table('departments')->where('id', $appointment->department_id)->value('name');

        return $department . ' · ' . Carbon::parse($appointment->appointment_date)->format('j M Y');
    }
}