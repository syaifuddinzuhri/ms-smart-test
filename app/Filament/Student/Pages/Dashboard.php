<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExamSessionStatus;
use App\Models\ExamSession;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.student.pages.dashboard';

    public function mount()
    {
        /**
         * LOGIC RESET AKSES GLOBAL:
         * Setiap kali siswa masuk ke Dashboard, cari semua sesi ujian milik siswa ini
         * yang masih memiliki token/system_id aktif, lalu set menjadi null.
         * Ini adalah lapis keamanan jika siswa berhasil 'back' ke Dashboard.
         */
        ExamSession::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->whereNotNull('token')
                    ->orWhereNotNull('system_id');
            })
            ->update([
                'token' => null,
                'system_id' => null,
                'status' => ExamSessionStatus::PAUSE
            ]);
    }
}
