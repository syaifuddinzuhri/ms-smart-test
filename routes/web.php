<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Ambil domain bersih tanpa http
$mainDomain = str_replace(['http://', 'https://'], '', config('app.app_domain'));

Route::domain($mainDomain)->group(function () {
    Route::get('/', function () {
        // Arahkan ke subdomain student jika domain utama diakses
        return redirect()->to('http://' . config('app.student_url'));
    });
});

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/login', function () {
//     if (auth()->check()) {
//         $role = auth()->user()->role->value;
//         return ($role === UserRole::STUDENT->value) ? redirect('/student') : redirect('/admin');
//     }
//     return redirect('/'); // Kirim ke Landing Page untuk pilih Login
// })->name('login');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');


Route::get('/pandoc', function () {
    $os = PHP_OS;
    $pandocPath = 'pandoc'; // Default fallback

    // 1. Logika Deteksi Path Berdasarkan OS
    if (strtoupper(substr($os, 0, 3)) === 'WIN') {
        // Windows: Cek di PATH atau lokasi default installer .msi
        $winCheck = shell_exec("where pandoc");
        $pandocPath = $winCheck ? 'pandoc' : 'C:\Program Files\Pandoc\pandoc.exe';
    } elseif ($os === 'Darwin') {
        // macOS: Cek lokasi Homebrew (Apple Silicon atau Intel)
        $macPaths = ['/opt/homebrew/bin/pandoc', '/usr/local/bin/pandoc'];
        foreach ($macPaths as $path) {
            if (file_exists($path)) {
                $pandocPath = $path;
                break;
            }
        }
    } else {
        // Ubuntu/Linux: Cek menggunakan command 'which'
        $linuxCheck = trim(shell_exec("which pandoc"));
        $pandocPath = !empty($linuxCheck) ? $linuxCheck : '/usr/bin/pandoc';
    }

    // 2. Eksekusi Cek Versi
    // Tambahkan 2>&1 untuk menangkap error jika binary tidak bisa dijalankan
    $output = shell_exec(escapeshellarg($pandocPath) . " -v 2>&1");
    $exists = file_exists($pandocPath) || !empty(shell_exec("which pandoc") ?? shell_exec("where pandoc"));

    // 3. Tampilkan Hasil Debugging
    return response()->json([
        'detected_os' => $os,
        'target_path' => $pandocPath,
        'is_file_exists' => file_exists($pandocPath),
        'can_execute' => !empty($output) && !str_contains($output, 'not found'),
        'version_output' => $output ? trim($output) : 'Gagal menjalankan command. Cek permissions atau disable_functions di php.ini.',
    ]);
});
