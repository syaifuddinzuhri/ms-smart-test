<?php

namespace App\Filament\Resources\ExamResultResource\Traits;

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

trait HasResultActions
{
    public static function getMonitoringBulkActions(): array
    {
        return [
            BulkAction::make('bulkFinalize')
                ->label('Finalisasi Nilai (Massal)')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalisasi Nilai Massal?')
                ->modalDescription('Semua sesi yang dipilih akan dikunci. Nilai tidak akan bisa diubah lagi setelah proses ini.')
                ->action(fn(\Illuminate\Support\Collection $records) => static::processBulkFinalize($records))
                ->deselectRecordsAfterCompletion(),

            BulkAction::make('bulkAddDuration')
                ->label('Tambah Durasi (Massal)')
                ->icon('heroicon-m-clock')
                ->color('info')
                ->form([
                    TextInput::make('minutes')
                        ->label('Tambahan Waktu (Menit)')
                        ->numeric()
                        ->required()
                        ->helperText('Sesi yang sudah selesai akan otomatis dilanjutkan kembali saat durasi ditambahkan.')
                        ->minValue(1)
                        ->suffix('Menit'),
                    TextInput::make('reason')
                        ->label('Alasan Penambahan Waktu')
                        ->default('Penambahan Massal oleh Admin')
                        ->required(),
                ])
                ->visible(
                    fn(ExamSession $record) =>
                    $record->status === ExamSessionStatus::COMPLETED && !$record->finalized_at
                )
                ->action(fn(\Illuminate\Support\Collection $records, array $data) => static::processBulkAddDuration($records, $data)),

            BulkAction::make('bulkReset')
                ->label('Reset Ujian yang Dipilih')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Ujian Massal?')
                ->modalDescription('Seluruh progres jawaban peserta yang dipilih akan dihapus permanen.')
                ->visible(
                    fn(ExamSession $record) =>
                    $record->status === ExamSessionStatus::COMPLETED && !$record->finalized_at
                )
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
                static::getFinalizeAction(),
                static::getAddDurationAction(),
                static::getResetAnswersAction(),
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

    protected static function getFinalizeAction(): Action
    {
        return Action::make('finalize')
            ->label('Finalisasi Nilai')
            ->icon('heroicon-o-lock-closed')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Finalisasi Hasil Ujian?')
            ->modalDescription('Setelah difinalisasi, nilai akan dikunci dan sesi tidak dapat diubah atau di-reset lagi.')
            // HANYA MUNCUL JIKA: Sudah Selesai DAN Belum Final
            ->visible(
                fn(ExamSession $record) =>
                $record->status === ExamSessionStatus::COMPLETED && !$record->finalized_at
            )
            ->action(fn(ExamSession $record) => static::processFinalize($record));
    }

    protected static function processBulkFinalize(\Illuminate\Support\Collection $records): void
    {
        $count = 0;
        foreach ($records as $record) {
            // Hanya finalisasi yang statusnya sudah COMPLETED
            if ($record->status === ExamSessionStatus::COMPLETED && !$record->finalized_at) {
                $record->update(['finalized_at' => now()]);
                $count++;
            }
        }

        Notification::make()
            ->title('Proses Finalisasi Selesai')
            ->body("$count data berhasil difinalisasi.")
            ->success()
            ->send();
    }

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
                    \App\Helpers\ExamTimeHelper::extendSession($record, (int) $data['minutes'], $data['reason'], true);
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

    protected static function getAddDurationAction(): Action
    {
        return Action::make('addDuration')
            ->label('Tambah Durasi')
            ->icon('heroicon-m-clock')
            ->color('info')
            ->modalWidth(MaxWidth::Medium)
            ->form([
                TextInput::make('minutes')
                    ->label('Tambahan Waktu (Menit)')
                    ->numeric()
                    ->required()
                    ->helperText('Sesi yang sudah selesai akan otomatis dilanjutkan kembali saat durasi ditambahkan.')
                    ->minValue(1)
                    ->suffix('Menit'),
                TextInput::make('reason')
                    ->label('Alasan Penambahan Waktu')
                    ->placeholder('Tidak Ada Alasan')
                    ->hint('Opsional'),
            ])
            ->visible(
                fn(ExamSession $record) =>
                $record->status === ExamSessionStatus::COMPLETED && !$record->finalized_at
            )
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
            ->visible(
                fn(ExamSession $record) =>
                $record->status === ExamSessionStatus::COMPLETED && !$record->finalized_at
            )
            ->action(fn(ExamSession $record) => static::processResetAnswers($record));
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

    protected static function processFinalize(ExamSession $record): void
    {
        $record->update([
            'finalized_at' => now(),
        ]);

        Notification::make()
            ->title('Sesi Berhasil Difinalisasi')
            ->body("Nilai untuk {$record->user->name} telah dikunci.")
            ->success()
            ->send();
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

                \App\Helpers\ExamTimeHelper::extendSession($record, $minutes, $data['reason'], true);
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
                    </div>
                    <p class='text-sm text-gray-700'>$reason</p>
                </div>";
        }
        return $html . "</div>";
    }
}
