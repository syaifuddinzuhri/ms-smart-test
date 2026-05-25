<?php

namespace App\Console\Commands;

use App\Enums\ExamSessionStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\ExamAnswer;
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

        $exam = Exam::with(['questions.options'])->find($examId);

        if (!$exam) {
            $this->error("Ujian dengan ID '{$examId}' tidak ditemukan.");
            return self::FAILURE;
        }

        $this->info("Ujian   : {$exam->title}");
        $this->info("Poin PG : {$exam->point_pg} | Poin TF: {$exam->point_true_false} | Poin Isian: {$exam->point_short_answer}");

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
                DB::transaction(function () use ($session, $exam, $isDryRun) {
                    $answers = ExamAnswer::with(['question.options', 'selectedOptions'])
                        ->where('exam_session_id', $session->id)
                        ->get();

                    foreach ($answers as $answer) {
                        $question = $answer->question;

                        // Essay: skor manual, tidak dihitung ulang
                        if ($question->isEssay()) {
                            continue;
                        }

                        $hasContent = $question->isPg()
                            ? $answer->selectedOptions->count() > 0
                            : trim(strip_tags($answer->answer_text ?? '')) !== '';

                        // Jawaban kosong: syncSessionScores sudah menangani pinalti null
                        if (!$hasContent) {
                            continue;
                        }

                        $isCorrect = $this->determineIsCorrect($answer, $question);
                        $newScore = $this->computeScore($isCorrect, $question, $exam);

                        if (!$isDryRun) {
                            $answer->update([
                                'is_correct' => $isCorrect,
                                'score' => $newScore,
                            ]);
                        }
                    }

                    if (!$isDryRun) {
                        $this->examService->syncSessionScores($session);
                    }
                });

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

    private function determineIsCorrect(ExamAnswer $answer, $question): bool
    {
        return match ($question->question_type) {
            QuestionType::SINGLE_CHOICE, QuestionType::TRUE_FALSE => $this->checkSingleChoice($answer, $question),
            QuestionType::MULTIPLE_CHOICE => $this->checkMultipleChoice($answer, $question),
            QuestionType::SHORT_ANSWER => $this->checkShortAnswer($answer, $question),
            default => false,
        };
    }

    private function checkSingleChoice(ExamAnswer $answer, $question): bool
    {
        $correctOption = $question->options->where('is_correct', true)->first();
        $selectedOption = $answer->selectedOptions->first();

        return $selectedOption && $correctOption && ($selectedOption->id === $correctOption->id);
    }

    private function checkMultipleChoice(ExamAnswer $answer, $question): bool
    {
        $correctIds = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->toArray();
        $selectedIds = $answer->selectedOptions->pluck('id')->sort()->values()->toArray();

        return $correctIds === $selectedIds;
    }

    private function checkShortAnswer(ExamAnswer $answer, $question): bool
    {
        $studentText = trim(strtolower($answer->answer_text ?? ''));
        $keys = collect(explode('|', $question->correct_answer_text ?? ''))
            ->map(fn($k) => trim(strtolower($k)))
            ->filter();

        return $keys->contains($studentText);
    }

    private function computeScore(bool $isCorrect, $question, Exam $exam): float
    {
        if ($isCorrect) {
            return match (true) {
                $question->isTrueFalse() => (float) $exam->point_true_false,
                $question->isPg() => (float) $exam->point_pg,
                $question->isShortAnswer() => (float) $exam->point_short_answer,
                default => 0,
            };
        }

        return match (true) {
            $question->isTrueFalse() => -(float) $exam->point_true_false_wrong,
            $question->isPg() => -(float) $exam->point_pg_wrong,
            $question->isShortAnswer() => -(float) $exam->point_short_answer_wrong,
            default => 0,
        };
    }
}
