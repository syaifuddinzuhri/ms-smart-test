<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Enums\QuestionType;
use App\Models\Exam;
use Exception;

class ExamService
{
    /**
     * Hitung total poin maksimal yang bisa didapat dari sebuah ujian.
     * Digunakan sebagai pembagi untuk normalisasi skor (misal ke skala 100).
     */
    public function getMaxPossibleRawScore(Exam $exam): float
    {
        $exam->loadMissing(['questions']);

        // Cache hasil perhitungan jika perlu untuk performa
        $questions = $exam->questions;

        $total = 0;
        foreach ($questions as $q) {
            $total += match (true) {
                $q->isPg() => (float) $exam->point_pg,
                $q->isShortAnswer() => (float) $exam->point_short_answer,
                $q->isEssay() => (float) $exam->point_essay_max,
                default => 0
            };
        }

        return $total > 0 ? $total : 1; // Hindari pembagian dengan nol
    }

    /**
     * Hitung Skor mentah tanpa update ke database
     */
    public function calculateScore(ExamAnswer $answer): ?float
    {
        $answer->load([
            'question.options',
            'selectedOptions',
            'session.exam'
        ]);
        $question = $answer->question;
        $exam = $answer->session->exam;

        if ($question->question_type === QuestionType::ESSAY) {
            return $answer->score;
        }

        $isCorrect = false;
        $score = 0;

        switch ($question->question_type) {
            case QuestionType::SINGLE_CHOICE:
            case QuestionType::TRUE_FALSE:
                $correctOption = $question->options->where('is_correct', true)->first();
                $selectedOption = $answer->selectedOptions->first();
                $isCorrect = $selectedOption && $correctOption && ($selectedOption->id === $correctOption->id);
                $score = $isCorrect ? $exam->point_pg : $exam->point_pg_wrong;
                break;

            case QuestionType::MULTIPLE_CHOICE:
                $correctOptionIds = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->toArray();
                $selectedOptionIds = $answer->selectedOptions->pluck('id')->sort()->values()->toArray();
                $isCorrect = ($correctOptionIds === $selectedOptionIds);
                $score = $isCorrect ? $exam->point_pg : $exam->point_pg_wrong;
                break;

            case QuestionType::SHORT_ANSWER:
                $studentText = trim(strtolower($answer->answer_text ?? ''));
                $keys = collect(explode('|', $question->correct_answer_text ?? ''))->map(fn($k) => trim(strtolower($k)))->filter();
                $isCorrect = $keys->contains($studentText);
                $score = $isCorrect ? $exam->point_short_answer : $exam->point_short_answer_wrong;
                break;
        }

        return (float) $score;
    }

    public function updateIncrementalScore(ExamSession $session, QuestionType $questionType, float $oldScore, float $newScore): void
    {
        $diff = $newScore - $oldScore;
        if ($diff == 0)
            return;

        $exam = $session->exam;

        $targetMaxScore = $exam->target_max_score;

        $column = match ($questionType) {
            QuestionType::SINGLE_CHOICE, QuestionType::MULTIPLE_CHOICE, QuestionType::TRUE_FALSE => 'score_pg',
            QuestionType::SHORT_ANSWER => 'score_short_answer',
            QuestionType::ESSAY => 'score_essay',
            default => null
        };

        if ($column) {
            $session->increment($column, $diff);
            // 2. Update Total Score
            if ($targetMaxScore) {
                // Jika ada normalisasi, kita hitung selisih proporsionalnya
                $maxRaw = $this->getMaxPossibleRawScore($exam);
                $normalizedDiff = $maxRaw > 0 ? ($diff / $maxRaw) * $targetMaxScore : 0;
                $session->increment('total_score', $normalizedDiff);
            } else {
                // Jika tidak ada normalisasi, tambah mentah-mentah
                $session->increment('total_score', $diff);
            }
        }
    }

    /**
     * Verifikasi Manual oleh Admin/Pengawas
     * Menentukan status jawaban (Benar/Salah) dan sistem otomatis menghitung poinnya.
     */
    public function manualVerify(ExamAnswer $answer, bool $isCorrect, ?float $essayScore = null): void
    {
        $answer->load([
            'session.exam',
            'question'
        ]);
        $exam = $answer->session->exam;
        $question = $answer->question;
        $oldScore = (float) $answer->score;

        $newScore = 0;

        // 1. LOGIKA KHUSUS ESSAY
        if ($question->isEssay()) {
            // Jika admin memberikan input angka skor
            if ($essayScore !== null) {
                // Validasi: Skor tidak boleh melebihi batas maksimal di pengaturan ujian
                if ($essayScore > $exam->point_essay_max) {
                    throw new Exception("Skor gagal disimpan. Maksimal poin essay untuk ujian ini adalah {$exam->point_essay_max}");
                }
                if ($essayScore < 0) {
                    throw new Exception("Skor tidak boleh kurang dari 0.");
                }

                $newScore = $essayScore ?? ($isCorrect ? $answer->session->exam->point_essay_max : 0);
                // Ketentuan: Skor > 0 dianggap True (Benar), Skor 0 dianggap False (Salah)
                $isCorrect = $newScore > 0;
            } else {
                // Jika admin tidak input angka (hanya klik tombol Benar/Salah)
                $newScore = $isCorrect ? $exam->point_essay_max : 0;
            }
        }

        // 2. LOGIKA UNTUK NON-ESSAY (PG & SHORT ANSWER)
        else {
            if ($isCorrect) {
                // JIKA ADMIN MENYATAKAN BENAR
                $newScore = match (true) {
                    $question->isPg() => $exam->point_pg,
                    $question->isShortAnswer() => $exam->point_short_answer,
                    default => 0
                };
            } else {
                // JIKA ADMIN MENYATAKAN SALAH
                $newScore = match (true) {
                    $question->isPg() => $exam->point_pg_wrong,
                    $question->isShortAnswer() => $exam->point_short_answer_wrong,
                    default => 0
                };
            }
        }

        // 3. SIMPAN PERUBAHAN
        $answer->update([
            'is_correct' => $isCorrect,
            'score' => $newScore,
        ]);

        // 4. SINKRONISASI TOTAL SKOR SESI
        // Panggil incremental
        $this->updateIncrementalScore($answer->session, $answer->question->question_type, $oldScore, $newScore);
    }

    /**
     * Sinkronisasi total skor PG, Isian, Essay ke tabel ExamSession.
     * Termasuk menangani poin untuk soal yang tidak dijawab (null point).
     */
    public function syncSessionScores(ExamSession $session): void
    {
        $session->loadMissing([
            'exam.questions',
        ]);
        $exam = $session->exam;

        $targetMaxScore = $exam->target_max_score;

        // 1. Ambil semua soal dalam ujian ini
        $questions = $exam->questions; // Pastikan relasi questions ada di Model Exam

        // 2. Ambil semua jawaban yang sudah masuk
        $answers = ExamAnswer::where('exam_session_id', $session->id)->get()->keyBy('question_id');

        $totalPg = 0;
        $totalShort = 0;
        $totalEssay = 0;

        foreach ($questions as $q) {
            $answer = $answers->get($q->id);

            /**
             * Logika Penentuan Jawaban Kosong:
             * 1. Benar-benar tidak ada data answer.
             * 2. Ada data answer, tapi is_doubtful DAN tidak sedang forceSubmit.
             * 3. (Opsional) Jika forceSubmit tapi answer memang kosong (null/string kosong), tetap dianggap null point.
             */
            $isAnswerMissing = !$answer || $answer->is_doubtful;

            // Cek tambahan jika forceSubmit tapi isinya memang kosong
            if ($answer && $answer->is_doubtful) {
                /**
                 * Memastikan konten jawaban benar-benar ada.
                 * - empty($answer->answer_text) untuk tipe Isian/Essay.
                 * - ! $answer->options()->exists() untuk tipe Pilihan Ganda.
                 */
                $isPhysicallyEmpty = empty($answer->answer_text) && !$answer->options()->exists();

                if ($isPhysicallyEmpty) {
                    $isAnswerMissing = true;
                }
            }

            if ($isAnswerMissing) {
                // Point Null
                if ($q->isPg())
                    $totalPg += $exam->point_pg_null;
                if ($q->isShortAnswer())
                    $totalShort += $exam->point_short_answer_null;
                if ($q->isEssay())
                    $totalEssay += $exam->point_essay_null;
                continue;
            }

            if (!$answer) {
                // Point Null
                if ($q->isPg())
                    $totalPg += $exam->point_pg_null;
                if ($q->isShortAnswer())
                    $totalShort += $exam->point_short_answer_null;
                if ($q->isEssay())
                    $totalEssay += $exam->point_essay_null;
                continue;
            }

            // LOGIKA POIN DARI JAWABAN ADA
            if ($q->isPg())
                $totalPg += $answer->score;
            if ($q->isShortAnswer())
                $totalShort += $answer->score;
            if ($q->isEssay())
                $totalEssay += $answer->score;
        }

        $rawTotal = $totalPg + $totalShort + $totalEssay;
        $finalTotal = $rawTotal;

        if ($targetMaxScore) {
            $maxRaw = $this->getMaxPossibleRawScore($exam);
            $finalTotal = $maxRaw > 0 ? ($rawTotal / $maxRaw) * $targetMaxScore : 0;
        }

        $session->update([
            'score_pg' => $totalPg,
            'score_short_answer' => $totalShort,
            'score_essay' => $totalEssay,
            'total_score' => $finalTotal,
        ]);
    }
}
