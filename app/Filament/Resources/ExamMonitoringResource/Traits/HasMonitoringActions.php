<?php

namespace App\Filament\Resources\ExamMonitoringResource\Traits;

use App\Enums\ExamSessionStatus;
use App\Models\ExamSession;
use App\Services\ExamService;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\BulkAction;

trait HasMonitoringActions
{
    public static function getMonitoringBulkActions(): array
    {
        return [
            // 1. Bulk Tambah Durasi
            BulkAction::make('bulkAddDuration')
                ->label('Tambah Durasi (Massal)')
                ->icon('heroicon-m-clock')
                ->color('success')
                ->form([
                    TextInput::make('minutes')
                        ->label('Tambahan Waktu (Menit)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->suffix('Menit'),
                    TextInput::make('reason')
                        ->label('Alasan Penambahan Waktu')
                        ->default('Penambahan Massal oleh Admin')
                        ->required(),
                ])
                ->action(fn(\Illuminate\Support\Collection $records, array $data) => static::processBulkAddDuration($records, $data)),

            // 2. Bulk Hentikan Ujian
            BulkAction::make('bulkForceSubmit')
                ->label('Hentikan Ujian (Massal)')
                ->icon('heroicon-m-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hentikan Ujian Sesi Terpilih?')
                ->modalDescription('Tindakan ini akan menghentikan sesi ujian peserta secara paksa. Semua jawaban yang ditandai sebagai "Ragu-ragu" namun sudah terisi akan otomatis dianggap sebagai jawaban final dan ikut dalam perhitungan skor.')
                ->modalSubmitActionLabel('Ya, Hentikan Ujian')
                ->action(fn(\Illuminate\Support\Collection $records) => static::processBulkForceSubmit($records)),

            // 3. Bulk Jeda
            BulkAction::make('bulkPause')
                ->label('Jeda Ujian (Massal)')
                ->icon('heroicon-m-pause')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn(\Illuminate\Support\Collection $records) => static::processBulkPause($records)),

            // 4. Bulk Reset (Menggantikan Delete)
            BulkAction::make('bulkReset')
                ->label('Reset Ujian yang Dipilih')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Ujian Massal?')
                ->modalDescription('Seluruh progres jawaban peserta yang dipilih akan dihapus permanen.')
                ->action(fn(\Illuminate\Support\Collection $records) => $records->each->delete())
                ->after(fn() => Notification::make()->title('Ujian berhasil di-reset')->success()->send()),
        ];
    }

    /**
     * Definisi Grup Aksi untuk Tabel Monitoring
     */
    public static function getMonitoringTableActions(): array
    {
        return [
            ActionGroup::make([
                static::getAddDurationAction(),
                static::getForceSubmitAction(),
                static::getResetAnswersAction(),
                static::getPauseSessionAction(),
                static::getViewViolationsAction(),
                static::getViewExtensionsAction(),
            ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }

    /* ----------------------------------------------------------- */
    /* DEFINISI KOMPONEN AKSI (UI & FORM)
    /* ----------------------------------------------------------- */

    protected static function processBulkAddDuration(\Illuminate\Support\Collection $records, array $data): void
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($records as $record) {
            try {
                if (!$record->expires_at) {
                    $failCount++;
                    continue;
                }

                DB::transaction(function () use ($record, $data) {
                    \App\Helpers\ExamTimeHelper::extendSession($record, (int) $data['minutes'], $data['reason']);
                });
                $successCount++;
            } catch (Exception $e) {
                $failCount++;
            }
        }

        if ($failCount === 0) {
            Notification::make()
                ->title('Proses Tambah Durasi Selesai')
                ->body("Berhasil: $successCount, Gagal: $failCount (Peserta belum mulai).")
                ->success()
                ->send();

        } else {
            Notification::make()
                ->title('Proses Tambah Durasi Selesai')
                ->body("Berhasil: $successCount, Gagal: $failCount (Peserta belum mulai).")
                ->warning()
                ->send();
        }

    }

    protected static function processBulkForceSubmit(\Illuminate\Support\Collection $records): void
    {
        $successCount = 0;
        $failedCount = 0;
        $examService = app(ExamService::class);

        foreach ($records as $record) {
            try {
                DB::transaction(function () use ($record, $examService) {
                    $record->refresh();

                    if ($record->status === ExamSessionStatus::COMPLETED) {
                        throw new Exception("Sesi sudah selesai.");
                    }

                    $examService->syncSessionScores($record);

                    $record->update([
                        'status' => ExamSessionStatus::COMPLETED,
                        'finished_at' => now(),
                        'token' => null,
                        'system_id' => null,
                    ]);
                });

                $successCount++;
            } catch (Exception $e) {
                $failedCount++;
            }
        }

        $message = new HtmlString("
            <div class='text-sm'>
                <p><b>Berhasil:</b> <span class='text-success-600 font-bold'>{$successCount}</span> data</p>
                <p><b>Gagal/Dilewati:</b> <span class='text-danger-600 font-bold'>{$failedCount}</span> data</p>
            </div>
        ");

        Notification::make()
            ->title('Proses Hentikan Ujian Selesai')
            ->body($message)
            ->color($failedCount > 0 ? 'warning' : 'success')
            ->icon($failedCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
            ->send();
    }

    protected static function processBulkPause(\Illuminate\Support\Collection $records): void
    {
        $successCount = 0;
        $failedCount = 0;

        foreach ($records as $record) {
            try {
                DB::transaction(function () use ($record) {
                    $record->refresh();

                    // Logika: Hanya sesi yang sedang ONGOING yang bisa di-pause
                    // Jika sesi sudah COMPLETED, kita masukkan ke kategori gagal/dilewati
                    if ($record->status !== ExamSessionStatus::ONGOING) {
                        throw new Exception("Hanya sesi aktif yang bisa dijeda.");
                    }

                    $record->update([
                        'token' => null,
                        'system_id' => null,
                        'status' => ExamSessionStatus::PAUSE
                    ]);
                });

                $successCount++;
            } catch (Exception $e) {
                $failedCount++;
            }
        }

        // Menyiapkan Body Notifikasi HTML
        $message = new HtmlString("
            <div class='text-sm'>
                <p><b>Berhasil Dijeda:</b> <span class='text-success-600 font-bold'>{$successCount}</span> data</p>
                <p><b>Gagal/Dilewati:</b> <span class='text-danger-600 font-bold'>{$failedCount}</span> data</p>
            </div>
        ");

        Notification::make()
            ->title('Proses Jeda Massal Selesai')
            ->body($message)
            ->color($failedCount > 0 ? 'warning' : 'success')
            ->icon('heroicon-o-pause-circle')
            ->send();
    }

    protected static function getAddDurationAction(): Action
    {
        return Action::make('addDuration')
            ->label('Tambah Durasi')
            ->icon('heroicon-m-clock')
            ->color('success')
            ->modalWidth(MaxWidth::Medium)
            ->form([
                TextInput::make('minutes')
                    ->label('Tambahan Waktu (Menit)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->suffix('Menit'),
                TextInput::make('reason')
                    ->label('Alasan Penambahan Waktu')
                    ->placeholder('Tidak Ada Alasan')
                    ->hint('Opsional'),
            ])
            ->action(fn(ExamSession $record, array $data) => static::processAddDuration($record, $data));
    }

    protected static function getForceSubmitAction(): Action
    {
        return Action::make('forceSubmit')
            ->label('Hentikan Ujian')
            ->icon('heroicon-m-stop-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hentikan Ujian?')
            ->modalDescription('Tindakan ini akan menghentikan sesi ujian peserta secara paksa. Semua jawaban yang ditandai sebagai "Ragu-ragu" namun sudah terisi akan otomatis dianggap sebagai jawaban final dan ikut dalam perhitungan skor.')
            ->action(fn(ExamSession $record) => static::processForceSubmit($record))
            ->visible(fn(ExamSession $record) => $record->status !== ExamSessionStatus::COMPLETED);
    }

    protected static function getResetAnswersAction(): Action
    {
        return Action::make('resetAnswers')
            ->label('Reset Ujian')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset Ujian?')
            ->modalDescription('Tindakan ini akan menghapus seluruh progres pengerjaan peserta ini.')
            ->action(fn(ExamSession $record) => static::processResetAnswers($record));
    }

    protected static function getPauseSessionAction(): Action
    {
        return Action::make('pauseSession')
            ->label('Jeda Ujian')
            ->icon('heroicon-m-pause')
            ->color('info')
            ->requiresConfirmation()
            ->action(fn(ExamSession $record) => static::processPauseSession($record))
            ->visible(fn(ExamSession $record) => $record->status !== ExamSessionStatus::COMPLETED);
    }

    protected static function getViewViolationsAction(): Action
    {
        return Action::make('viewViolations')
            ->label('Detail Pelanggaran')
            ->icon('heroicon-m-exclamation-triangle')
            ->color('warning')
            ->modalHeading('Riwayat Pelanggaran')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->form(fn(ExamSession $record) => [
                Placeholder::make('log_list')
                    ->label('')
                    ->content(new HtmlString(static::buildViolationLogHtml($record))),
            ]);
    }

    protected static function getViewExtensionsAction(): Action
    {
        return Action::make('viewExtensions')
            ->label('Riwayat Tambahan Waktu')
            ->icon('heroicon-m-clock')
            ->color('warning')
            ->modalHeading('Riwayat Tambahan Waktu')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->form(fn(ExamSession $record) => [
                Placeholder::make('extension_logs')
                    ->label('')
                    ->content(new HtmlString(static::buildExtensionLogHtml($record))),
            ]);
    }

    /* ----------------------------------------------------------- */
    /* LOGIKA PROSES DATABASE (DB PROCESS)
    /* ----------------------------------------------------------- */

    protected static function buildExtensionLogHtml(ExamSession $record): string
    {
        $record->refresh();
        $logs = $record->extension_log ?? [];

        if (empty($logs)) {
            return '<div class="text-sm text-gray-500 italic text-center py-4">Belum ada riwayat tambahan waktu untuk peserta ini.</div>';
        }

        $html = "<div class='space-y-3'>";
        foreach (array_reverse($logs) as $log) {
            $time = Carbon::parse($log['at'])->format('d/m/Y H:i T');
            $minutes = $log['minutes'] ?? 0;
            $admin = $log['by'] ?? 'System';
            $reason = $log['reason'] ?? 'Tidak ada alasan';

            $html .= "
                <div class='p-3 bg-white border border-gray-200 rounded-lg shadow-sm'>
                    <div class='flex justify-between items-start mb-2'>
                        <span class='px-2 py-1 bg-success-50 text-success-700 text-xs font-bold rounded'>+{$minutes} Menit</span>
                        <span class='text-[10px] text-gray-400 font-mono'>$time</span>
                    </div>
                    <div class='text-xs text-gray-600 mb-1'>
                        <span class='font-semibold text-gray-800'>Oleh:</span> $admin
                    </div>
                    <div class='text-xs text-gray-500 italic'>
                        \"$reason\"
                    </div>
                </div>";
        }
        $html .= "</div>";

        return $html;
    }

    protected static function processAddDuration(ExamSession $record, array $data): void
    {
        try {
            DB::transaction(function () use ($record, $data) {
                $record->refresh();
                $minutes = (int) $data['minutes'];

                if (!$record->expires_at) {
                    throw new Exception('Tidak bisa menambah waktu. Peserta belum memulai ujian.');
                }

                \App\Helpers\ExamTimeHelper::extendSession($record, $minutes, $data['reason']);
            });

            // Notifikasi Sukses
            Notification::make()
                ->title('Durasi berhasil ditambah')
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Gagal Menambah Durasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
            // Opsional: Jika ini di dalam Filament Action, Anda mungkin ingin
            // menghentikan proses agar modal tidak tertutup:
            // halt();
        }
    }

    protected static function processForceSubmit(ExamSession $record): void
    {
        try {
            DB::transaction(function () use ($record) {
                $record->refresh();

                if ($record->status === ExamSessionStatus::COMPLETED) {
                    Notification::make()
                        ->title('Terjadi kesalahan')
                        ->body('Sesi sudah selesai.')
                        ->warning()
                        ->send();
                    return;
                }

                app(ExamService::class)->syncSessionScores($record);

                $record->update([
                    'token' => null,
                    'system_id' => null,
                    'status' => ExamSessionStatus::COMPLETED,
                    'finished_at' => now(),
                ]);

            });

            Notification::make()
                ->title('Peserta berhasil diHentikan Ujian')
                ->body('Jawaban berhasil disimpan dan hasil didapatkan.')
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body($e->getMessage() ?? 'Gagal menyimpan hasil ujian.')
                ->warning()
                ->send();
        }
    }

    protected static function processResetAnswers(ExamSession $record): void
    {
        $record->delete();
        Notification::make()->title('Jawaban berhasil di-reset')->success()->send();
    }

    protected static function processPauseSession(ExamSession $record): void
    {
        $record->refresh();

        if ($record->status !== ExamSessionStatus::ONGOING) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Hanya sesi aktif yang bisa dijeda.')
                ->warning()
                ->send();
            return;
        }

        $record->update([
            'token' => null,
            'system_id' => null,
            'status' => ExamSessionStatus::PAUSE
        ]);

        Notification::make()->title('Sesi berhasil dijeda')->success()->send();
    }

    protected static function buildViolationLogHtml(ExamSession $record): string
    {
        $record->refresh();

        $logs = $record->violation_log ?? [];
        if (empty($logs))
            return 'Tidak ada data pelanggaran.';

        $html = "<div class='space-y-3'>";
        foreach (array_reverse($logs) as $log) {
            $time = Carbon::parse($log['time'])->format('d/m/Y H:i:s T');
            $reason = $log['reason'] ?? 'Keluar dari area ujian';
            $step = $log['step'] ?? '-';
            $html .= "
                <div class='p-3 bg-gray-50 border-l-4 border-orange-500 rounded'>
                    <div class='flex justify-between font-bold text-xs text-gray-500 mb-1'>
                        <span>$time</span>
                        <span>Soal No: $step</span>
                    </div>
                    <p class='text-sm text-gray-700'>$reason</p>
                </div>";
        }
        return $html . "</div>";
    }
}
