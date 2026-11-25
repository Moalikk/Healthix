<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Batas dibaca dari .env supaya bisa dilonggarkan saat load test
        $loginLimit = (int) env('LOGIN_RATE_LIMIT', 5);       // percobaan login per menit (per email + IP)
        $registerLimit = (int) env('REGISTER_RATE_LIMIT', 10); // pendaftaran per menit (per IP)
        $bookingLimit = (int) env('BOOKING_RATE_LIMIT', 10);   // permohonan baru per menit (per user)

        $tooMany = fn (string $message) => function (Request $request, array $headers) use ($message) {
            $wait = $headers['Retry-After'] ?? 60;

            return response()->json(['message' => "$message Try again in $wait seconds."], 429, $headers);
        };

        RateLimiter::for('login', fn (Request $request) =>
            Limit::perMinute($loginLimit)
                ->by(strtolower((string) $request->input('email')) . '|' . $request->ip())
                ->response($tooMany('Too many login attempts.')));

        RateLimiter::for('register', fn (Request $request) =>
            Limit::perMinute($registerLimit)
                ->by($request->ip())
                ->response($tooMany('Too many registrations.')));

        RateLimiter::for('booking', fn (Request $request) =>
            Limit::perMinute($bookingLimit)
                ->by((string) ($request->user()?->id ?? $request->ip()))
                ->response($tooMany('Too many requests.')));
    }
}