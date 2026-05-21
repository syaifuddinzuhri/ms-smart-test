<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DatabaseManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Manajemen Database';
    protected static ?string $title = 'Manajemen Database';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.database-management';

    public static function canAccess(): bool
    {
        return auth()->user()?->role !== UserRole::TEACHER;
    }

    // =========================================================
    // ACTIONS
    // =========================================================

    public function backupDatabaseAction(): Action
    {
        return Action::make('backupDatabase')
            ->label('Download Backup')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action('runBackupDatabase');
    }

    public function resetUjianAction(): Action
    {
        return Action::make('resetUjian')
            ->label('Reset Sekarang')
            ->icon('heroicon-o-trash')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Reset Database Ujian?')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalDescription(new HtmlString(
                '<div class="text-sm text-gray-700 space-y-2">
                    <p>Data berikut akan <strong class="text-red-600">dihapus permanen</strong>:</p>
                    <ul class="list-disc ml-5 space-y-1 text-gray-600">
                        <li>Semua ujian (exams)</li>
                        <li>Token & sesi ujian peserta</li>
                        <li>Jawaban & soal yang ditautkan ke ujian</li>
                    </ul>
                    <p class="mt-2 text-gray-500 text-xs">Data soal, topik, mata pelajaran, dan peserta tetap aman.</p>
                </div>'
            ))
            ->form([
                Checkbox::make('confirm')
                    ->label('Saya memahami tindakan ini tidak dapat dibatalkan.')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Centang persetujuan untuk melanjutkan.']),
            ])
            ->modalSubmitActionLabel('Ya, Reset Ujian')
            ->action('runResetUjian');
    }

    public function resetPesertaAction(): Action
    {
        return Action::make('resetPeserta')
            ->label('Reset Sekarang')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset Database Peserta?')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalDescription(new HtmlString(
                '<div class="text-sm text-gray-700 space-y-2">
                    <p>Data berikut akan <strong class="text-red-600">dihapus permanen</strong>:</p>
                    <ul class="list-disc ml-5 space-y-1 text-gray-600">
                        <li>Semua akun peserta (siswa)</li>
                        <li>Semua data kelas</li>
                        <li>Semua ujian, sesi, token, dan jawaban</li>
                    </ul>
                    <p class="mt-2 text-gray-500 text-xs">Data soal, topik, dan mata pelajaran tetap aman.</p>
                </div>'
            ))
            ->form([
                Checkbox::make('confirm')
                    ->label('Saya memahami tindakan ini tidak dapat dibatalkan.')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Centang persetujuan untuk melanjutkan.']),
            ])
            ->modalSubmitActionLabel('Ya, Reset Peserta')
            ->action('runResetPeserta');
    }

    public function resetSoalAction(): Action
    {
        return Action::make('resetSoal')
            ->label('Reset Sekarang')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset Database Soal?')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalDescription(new HtmlString(
                '<div class="text-sm text-gray-700 space-y-2">
                    <p>Data berikut akan <strong class="text-red-600">dihapus permanen</strong>:</p>
                    <ul class="list-disc ml-5 space-y-1 text-gray-600">
                        <li>Semua soal beserta opsi & lampiran (termasuk file)</li>
                        <li>Soal yang ditautkan ke ujian & jawaban peserta</li>
                    </ul>
                    <p class="mt-2 text-gray-500 text-xs">Topik, mata pelajaran, ujian, peserta, dan kelas tetap aman.</p>
                </div>'
            ))
            ->form([
                Checkbox::make('confirm')
                    ->label('Saya memahami tindakan ini tidak dapat dibatalkan.')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Centang persetujuan untuk melanjutkan.']),
            ])
            ->modalSubmitActionLabel('Ya, Reset Soal')
            ->action('runResetSoal');
    }

    // =========================================================
    // HANDLERS
    // =========================================================

    public function runBackupDatabase(): mixed
    {
        $dbName = config('database.connections.mysql.database');
        $filename = 'backup_' . $dbName . '_' . now()->format('Y_m_d_His') . '.sql';

        try {
            $sql = $this->generateSql($dbName);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Backup Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return null;
        }

        return response()->streamDownload(function () use ($sql) {
            echo $sql;
        }, $filename, ['Content-Type' => 'application/octet-stream']);
    }

    public function runResetUjian(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::table('exam_answer_options')->truncate();
            DB::table('exam_answers')->truncate();
            DB::table('exam_sessions')->truncate();
            DB::table('exam_tokens')->truncate();
            DB::table('exam_questions')->truncate();
            DB::table('exam_classrooms')->truncate();
            DB::table('exams')->truncate();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Notification::make()
            ->title('Reset Ujian Berhasil')
            ->body('Semua data ujian, sesi, token, dan jawaban telah dihapus.')
            ->success()
            ->send();
    }

    public function runResetPeserta(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::table('exam_answer_options')->truncate();
            DB::table('exam_answers')->truncate();
            DB::table('exam_sessions')->truncate();
            DB::table('exam_tokens')->truncate();
            DB::table('exam_questions')->truncate();
            DB::table('exam_classrooms')->truncate();
            DB::table('exams')->truncate();
            DB::table('students')->truncate();
            DB::table('classrooms')->truncate();
            DB::table('users')->where('role', UserRole::STUDENT->value)->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Notification::make()
            ->title('Reset Peserta Berhasil')
            ->body('Semua data peserta, kelas, dan ujian telah dihapus.')
            ->success()
            ->send();
    }

    public function runResetSoal(): void
    {
        Storage::disk('public')->deleteDirectory('questions');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::table('exam_answer_options')->truncate();
            DB::table('exam_answers')->truncate();
            DB::table('exam_questions')->truncate();
            DB::table('question_attachments')->truncate();
            DB::table('question_options')->truncate();
            DB::table('questions')->truncate();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Notification::make()
            ->title('Reset Soal Berhasil')
            ->body('Semua soal, opsi, dan lampiran telah dihapus. Topik dan mata pelajaran tetap.')
            ->success()
            ->send();
    }

    // =========================================================
    // SQL GENERATOR
    // =========================================================

    protected function generateSql(string $dbName): string
    {
        $pdo = DB::getPdo();
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn($row) => array_values((array) $row)[0]);

        $lines = [];
        $lines[] = '-- ==============================================';
        $lines[] = "-- Database Backup: {$dbName}";
        $lines[] = '-- Generated: ' . now()->format('Y-m-d H:i:s') . ' (Asia/Jakarta)';
        $lines[] = '-- ==============================================';
        $lines[] = '';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = '';

        foreach ($tables as $table) {
            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql = array_values((array) $createResult[0])[1];

            $lines[] = "-- Table: `{$table}`";
            $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
            $lines[] = $createSql . ';';
            $lines[] = '';

            $rows = DB::table($table)->get();
            if ($rows->isNotEmpty()) {
                foreach ($rows->chunk(500) as $chunk) {
                    $rowStrings = $chunk->map(function ($row) use ($pdo) {
                        $values = array_map(function ($v) use ($pdo) {
                            return $v === null ? 'NULL' : $pdo->quote((string) $v);
                        }, (array) $row);
                        return '(' . implode(', ', $values) . ')';
                    });

                    $lines[] = "INSERT INTO `{$table}` VALUES";
                    $lines[] = $rowStrings->implode(",\n") . ';';
                    $lines[] = '';
                }
            }
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines);
    }
}
