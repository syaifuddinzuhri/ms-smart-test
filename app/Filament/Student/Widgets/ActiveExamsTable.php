<?php

namespace App\Filament\Student\Widgets;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\Facades\Auth;

class ActiveExamsTable extends BaseWidget
{
    protected static ?string $heading = 'Ujian Tersedia';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $classroomId = $user->student?->classroom_id;

        return $table
            ->query(
                Exam::query()
                    ->whereHas('classrooms', function ($query) use ($classroomId) {
                        $query->where('classroom_id', $classroomId);
                    })
                    ->whereHas('examQuestions')
                    ->whereDoesntHave('sessions', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('status', ExamSessionStatus::COMPLETED);
                    })
                    ->where(function ($query) use ($user) {
                        // Kondisi 1: Tampilkan jika ACTIVE
                        $query->where('status', ExamStatus::ACTIVE->value)
                            ->where('start_time', '<=', now())
                            // Optional: Ujian active tetap muncu jika sudah punya sesi meski end_time lewat
                            ->where(fn($q) => $q->where('end_time', '>=', now())
                                ->orWhereHas('sessions', fn($s) => $s->where('user_id', $user->id)));

                        // Kondisi 2: Tampilkan jika INACTIVE tapi peserta SUDAH PUNYA SESSION
                        $query->orWhere(function ($sub) use ($user) {
                            $sub->where('status', ExamStatus::INACTIVE->value)
                                ->whereHas('sessions', fn($s) => $s->where('user_id', $user->id));
                        });
                    })
                    ->with([
                        'sessions' => function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        }
                    ])
            )
            ->columns([
                Stack::make([
                    Tables\Columns\TextColumn::make('title')
                        ->label('Ujian / Mata Pelajaran')
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Large),

                    Tables\Columns\TextColumn::make('subject.name')
                        ->formatStateUsing(fn($state) => 'Mata Pelajaran: ' . $state)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                        ->color('gray'),

                    Split::make([
                        Tables\Columns\TextColumn::make('duration')
                            ->getStateUsing(function (Exam $exam) {
                                $session = $exam->sessions->first();

                                $baseDuration = $exam->duration ?? 0;

                                // 1. Ambil log dari session (individu)
                                $sessionLogs = collect($session->extension_log ?? []);

                                // 2. Ambil log dari exam (global)
                                $examLogs = collect($exam->extension_log ?? []);

                                /**
                                 * Logika Sesuai Request:
                                 * - Jika ada session log (individu), ambil sum dari situ.
                                 * - Jika session log kosong (meskipun ada session), atau belum ada session,
                                 *   ambil sum dari exam log (global).
                                 */
                                $additionalMinutes = $sessionLogs->isNotEmpty()
                                    ? $sessionLogs->sum('minutes')
                                    : $examLogs->sum('minutes');

                                return $baseDuration + $additionalMinutes;
                            })
                            ->badge()
                            ->color('danger'),

                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->getStateUsing(function ($record) {
                                if ($record->status === ExamStatus::INACTIVE) {
                                    return ExamStatus::INACTIVE->getLabel();
                                }

                                $session = $record->sessions->first();

                                if (!$session) {
                                    return ExamSessionStatus::NOT_STARTED->getLabel();
                                }

                                return $session->status->getLabel();
                            })
                            ->color(function (Exam $record) {
                                if ($record->status === ExamStatus::INACTIVE) {
                                    return 'gray';
                                }

                                $session = $record->sessions->first();
                                if (!$session)
                                    return 'warning';

                                return $session->status->getColor();
                            }),
                    ])->extraAttributes(['class' => 'mt-3 mb-2']),

                    Tables\Columns\TextColumn::make('start_time')
                        ->label('Jadwal Pelaksanaan')
                        ->icon('heroicon-m-calendar-days')
                        ->color('gray')
                        ->weight('medium')
                        ->formatStateUsing(function (Exam $record) {
                            $start = $record->start_time;
                            $end = $record->end_time;

                            if (!$start || !$end)
                                return '-';

                            if ($start->isSameDay($end)) {
                                return $start->translatedFormat('d F Y');
                            }

                            return $start->translatedFormat('d M Y, H:i T');
                        })
                        ->description(function (Exam $record): \Illuminate\Support\HtmlString {
                            $start = $record->start_time;
                            $end = $record->end_time;

                            if (!$start || !$end)
                                return new \Illuminate\Support\HtmlString('');

                            if ($start->isSameDay($end)) {
                                return new \Illuminate\Support\HtmlString("
                                <div class='flex items-center gap-1 text-primary-600 font-medium text-xs mt-0.5'>
                                    <svg class='w-3.5 h-3.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>
                                    <span>{$start->format('H:i')} — {$end->format('H:i T')}</span>
                                </div>
                            ");
                            }

                            return new \Illuminate\Support\HtmlString("
                            <div class='text-gray-500 text-xs mt-0.5'>
                                Selesai: <span class='text-gray-500'>{$end->translatedFormat('d M Y, H:i T')}</span>
                            </div>
                        ");
                        }),
                ])->space(2),
            ])
            ->actions([
                Tables\Actions\Action::make('mulai')
                    ->label(function (Exam $record) {
                        $session = $record->sessions->first();
                        if (!$session)
                            return 'Mulai Ujian';

                        return match ($session->status) {
                            ExamSessionStatus::PAUSE => 'Lanjutkan',
                            ExamSessionStatus::ONGOING => 'Sedang Dikerjakan',
                            ExamSessionStatus::COMPLETED => 'Sudah Selesai',
                            default => 'Belum Dikerjakan',
                        };
                    })
                    ->icon(function (Exam $record) {
                        $session = $record->sessions->first();
                        if (!$session)
                            return 'heroicon-m-play';

                        return match ($session->status) {
                            ExamSessionStatus::ONGOING => 'heroicon-m-arrow-path',
                            ExamSessionStatus::COMPLETED => 'heroicon-m-check-circle',
                            default => 'heroicon-m-play',
                        };
                    })
                    ->button()
                    ->color(function (Exam $record) {
                        if ($record->status === ExamStatus::INACTIVE)
                            return 'gray';

                        $session = $record->sessions->first();

                        if (!$session)
                            return 'primary';

                        return match ($session->status) {
                            ExamSessionStatus::PAUSE => 'info',
                            ExamSessionStatus::ONGOING => 'warning',
                            ExamSessionStatus::COMPLETED => 'success',
                            default => 'gray',
                        };
                    })
                    ->extraAttributes([
                        'class' => 'w-full md:w-auto mt-4 justify-center',
                    ])
                    ->url(function ($record) {
                        if ($record->status === ExamStatus::INACTIVE)
                            return null;

                        return route('filament.student.pages.input-token', ['exam_id' => $record->id]);
                    })
                    ->disabled(function ($record) {
                        $session = $record->sessions->first();
                        // Tombol mati jika:
                        // 1. Ujian INACTIVE (Admin mematikan ujian sementara)
                        // 2. ATAU Siswa sudah selesai (COMPLETED)
                        return $record->status === ExamStatus::INACTIVE ||
                            ($session && $session->status === ExamSessionStatus::COMPLETED);
                    }),
            ])
            ->paginated(false);
    }
}
