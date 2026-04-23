<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExamSessionStatus;
use App\Models\ExamSession;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.student.pages.dashboard';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function mount()
    {
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
