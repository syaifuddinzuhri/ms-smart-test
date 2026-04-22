<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Beritahu Laravel untuk tidak mengenkripsi cookie session agar transisi lancar
        $middleware->encryptCookies(except: [
            'ms_admin_session',
            'ms_student_session',
        ]);

        // 2. Set Cookie Nama secara dinamis sebelum session dimulai
        $middleware->alias([
            // Jika Anda masih ingin menggunakan middleware kustom, pastikan terdaftar di sini
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
