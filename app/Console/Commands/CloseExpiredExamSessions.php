<?php

namespace App\Console\Commands;

use App\Enums\ExamSessionStatus;
use App\Models\ExamSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseExpiredExamSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:close-expired-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menutup sesi ujian yang sudah habis waktunya atau melewati batas jadwal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::transaction(function () {
            // 1. Ambil Sesi yang:
            // - Status masih ONGOING atau PAUSE (terkunci)
            // - DAN (Sisa durasi sudah 0 ATAU waktu akhir ujian sudah lewat + 15 menit)

            $affectedRows = ExamSession::whereIn('status', [
                ExamSessionStatus::ONGOING,
                ExamSessionStatus::PAUSE
            ])
                ->where('updated_at', '>', now()->subDay())
                ->where(function ($query) {
                    $query->where('remaining_duration', '<=', 0) // Kondisi durasi habis
                        ->orWhereHas('exam', function ($q) {
                            $q->where('end_time', '<', now()->subMinutes(15)); // Kondisi jadwal habis
                        });
                })
                ->update([
                    'status' => ExamSessionStatus::COMPLETED,
                    'finished_at' => now(),
                    'token' => null,      // Keamanan: Hapus token agar tidak bisa ditembus lagi
                    'system_id' => null,  // Keamanan: Lepas binding device
                ]);

            $this->info("Berhasil menutup {$affectedRows} sesi ujian yang expired.");
        });
    }
}
