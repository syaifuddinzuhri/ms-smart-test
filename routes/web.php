<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    if (auth()->check()) {
        $role = auth()->user()->role->value;
        return ($role === UserRole::STUDENT->value) ? redirect('/student') : redirect('/admin');
    }
    return redirect('/'); // Kirim ke Landing Page untuk pilih Login
})->name('login');


Route::get('/test-pandoc', function () {
    $output = shell_exec('/opt/homebrew/bin/pandoc -v');
    return "<pre>$output</pre>";
});
