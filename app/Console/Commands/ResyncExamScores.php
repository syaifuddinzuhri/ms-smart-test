<?php

namespace App\Console\Commands;

use App\Enums\ExamSessionStatus;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResyncExamScores extends Command
{
    protected $signature = 'exam:resync-scores
                            {examId : UUID ujian yang ingin diresync}
                            {--dry-run : Simulasi tanpa mengubah data}';

    protected $description = 'Hitung ulang skor jawaban dan sinkronisasi skor akhir semua sesi dari ujian tertentu';

    public function __construct(private ExamService $examService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $examId = $this->argument('examId');
        $isDryRun = $this->option('dry-run');

        $exam = Exam::find($examId);

        if (!$exam) {
            $this->error("Ujian dengan ID '{$examId}' tidak ditemukan.");
            return self::FAILURE;
        }

        $this->info("Ujian    : {$exam->title}");
        $this->info("Poin PG  : {$exam->point_pg} | TF: {$exam->point_true_false} | Isian: {$exam->point_short_answer}");

        if ($isDryRun) {
            $this->warn("[DRY RUN] Mode simulasi aktif — tidak ada data yang akan diubah.");
        }

        $sessions = ExamSession::where('exam_id', $examId)
            ->whereIn('status', [
                ExamSessionStatus::COMPLETED,
                ExamSessionStatus::ONGOING,
                ExamSessionStatus::PAUSE,
            ])
            ->get();

        if ($sessions->isEmpty()) {
            $this->warn("Tidak ada sesi ujian yang ditemukan untuk ujian ini.");
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$sessions->count()} sesi ujian. Memulai resync...");
        $this->newLine();

        $bar = $this->output->createProgressBar($sessions->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($sessions as $session) {
            try {
                if (!$isDryRun) {
                    DB::transaction(function () use ($session) {
                        $this->examService->resyncAnswerScores($session);
                        $this->examService->syncSessionScores($session);
                    });
                }
                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Gagal memproses sesi {$session->id}: {$e->getMessage()}");
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->info("[DRY RUN] Simulasi selesai. {$successCount} sesi akan diproses, {$failCount} akan gagal.");
        } else {
            $this->info("Selesai! Berhasil: {$successCount} sesi | Gagal: {$failCount} sesi.");
        }

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
