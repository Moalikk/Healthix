<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueueService
{
    const LATE_MINUTES = 10; // batas keterlambatan setelah dipanggil
    const MAX_SKIPS = 2;     // setelah dilewati sekian kali -> missed

    // Pasien 'called' yang lewat 10 menit dipindah ke antrean paling akhir
    public static function skipLate(): void
    {
        $cutoff = now()->subMinutes(self::LATE_MINUTES);

        $lateIds = DB::table('appointments')
            ->where('status', 'called')
            ->where('called_at', '<=', $cutoff)
            ->pluck('id');

        foreach ($lateIds as $id) {
            DB::transaction(function () use ($id, $cutoff) {
                $a = DB::table('appointments')->where('id', $id)->lockForUpdate()->first();

                // Sudah ditangani proses lain, atau belum benar-benar telat
                if (!$a || $a->status !== 'called' || Carbon::parse($a->called_at)->gt($cutoff)) {
                    return;
                }

                $skips = $a->skip_count + 1;

                if ($skips >= self::MAX_SKIPS) {
                    DB::table('appointments')->where('id', $id)->update([
                        'status'     => 'missed',
                        'skip_count' => $skips,
                        'updated_at' => now(),
                    ]);

                    Notifier::send(
                        $a->user_id,
                        $a->id,
                        'Request marked as missed',
                        Notifier::label($a) . '. You did not arrive in time. Please submit a new request.'
                    );
                    return;
                }

                $last = DB::table('appointments')
                    ->where('appointment_date', $a->appointment_date)
                    ->where('department_id', $a->department_id)
                    ->whereNotNull('queue_number')
                    ->lockForUpdate()
                    ->max('queue_number');

                DB::table('appointments')->where('id', $id)->update([
                    'status'       => 'confirmed',
                    'queue_number' => $last + 1,
                    'called_at'    => null,
                    'skip_count'   => $skips,
                    'updated_at'   => now(),
                ]);

                Notifier::send(
                    $a->user_id,
                    $a->id,
                    'Moved to the end of the queue',
                    Notifier::label($a) . '. You did not arrive within ' . self::LATE_MINUTES . ' minutes. New queue number: #' . ($last + 1) . '.'
                );
            });
        }
    }
}