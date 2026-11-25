<?php

use Illuminate\Support\Facades\Route;

/*
| Menyajikan folder frontend/ lewat Laravel, supaya website dan API satu alamat.
| Buka: http://127.0.0.1:8000/app/login.html
| Berguna untuk dibuka dari laptop lain atau lewat terowongan (tunnel).
*/
Route::get('/', fn () => redirect('/app/quickcare-index.html'));

Route::get('/app/{path?}', function (string $path = 'quickcare-index.html') {
    $root = realpath(base_path('../frontend'));
    $file = $root ? realpath($root . DIRECTORY_SEPARATOR . $path) : false;

    // Cegah akses di luar folder frontend (../)
    abort_unless($file && is_file($file) && str_starts_with($file, $root . DIRECTORY_SEPARATOR), 404);

    $types = [
        'html' => 'text/html; charset=UTF-8',
        'css'  => 'text/css; charset=UTF-8',
        'js'   => 'application/javascript; charset=UTF-8',
        'json' => 'application/json',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'  => 'font/ttf',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    return response()->file($file, ['Content-Type' => $types[$ext] ?? 'application/octet-stream']);
})->where('path', '.*');