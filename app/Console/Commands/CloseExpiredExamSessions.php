<?php

namespace App\Console\Commands;

use App\Enums\ExamSessionStatus;
use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseExpiredExamSessions extends Command
{
    protected $signature = 'exam:close-expired-sessions';
    protected $description = 'Menutup sesi ujian yang sudah melewati deadline pengerjaan atau jadwal global';

    public function handle()
    {
        $this->info("Memulai proses pemeriksaan sesi kadaluarsa...");

        ExamSession::query()
            ->whereIn('status', [
                ExamSessionStatus::ONGOING,
                ExamSessionStatus::PAUSE
            ])
            ->with('exam')
            ->each(function (ExamSession $session) {
                try {
                    DB::transaction(function () use ($session) {
                        $now = now();
                        if (!$session->exam)
                            return;

                        // 1. Cek Deadline Jadwal Global (Gerbang Ujian + Toleransi 15 menit)
                        $isPastEndTime = $session->exam->end_time->addMinutes(15)->isPast();

                        // 2. Cek Deadline Individu (Siswa harus selesai jam sekian)
                        // Karena expires_at adalah waktu statis, kita cukup cek apakah sudah lewat
                        $isPastExpiresAt = $session->expires_at && $session->expires_at->isPast();

                        if ($isPastEndTime || $isPastExpiresAt) {
                            // Tentukan waktu selesai yang paling akurat
                            // Jika karena durasi habis, pakai expires_at. Jika karena jadwal ditutup, pakai now.
                            $finishedAt = ($isPastExpiresAt) ? $session->expires_at : $now;

                            app(ExamService::class)->syncSessionScores($session);

                            $session->update([
                                'status' => ExamSessionStatus::COMPLETED,
                                'finished_at' => $finishedAt,
                                'token' => null,
                                'system_id' => null,
                            ]);
                        }
                    });
                } catch (\Exception $e) {
                    $this->error("Gagal memproses sesi ID: {$session->id}. Error: {$e->getMessage()}");
                }
            });

        $this->info("Proses pembersihan selesai.");
    }
}
