<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Enums\QuestionType;

class ExamService
{
    /**
     * Menghitung skor untuk satu jawaban spesifik dan menyimpannya.
     */
    public function gradeAnswer(ExamAnswer $answer): void
    {
        $answer->loadMissing([
            'question.options',
            'selectedOptions',
            'session.exam'
        ]);
        $question = $answer->question;
        $exam = $answer->session->exam;

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
                $correctText = trim(strtolower($question->answer_key ?? ''));
                $studentText = trim(strtolower($answer->answer_text ?? ''));

                $isCorrect = ($correctText !== '' && $correctText === $studentText);
                $score = $isCorrect ? $exam->point_short_answer : $exam->point_short_answer_wrong;
                break;

            case QuestionType::ESSAY:
                // Essay tidak bisa dinilai otomatis secara akurat,
                // default skor 0 sampai dinilai manual oleh guru.
                $isCorrect = null;
                $score = $answer->score ?? 0;
                break;
        }

        $answer->update([
            'is_correct' => $isCorrect,
            'score' => $score
        ]);
    }

    /**
     * Sinkronisasi total skor PG, Isian, Essay ke tabel ExamSession.
     * Termasuk menangani poin untuk soal yang tidak dijawab (null point).
     */
    public function syncSessionScores(ExamSession $session): void
    {
        $session->loadMissing([
            'exam.questions',
            'questions'
        ]);
        $exam = $session->exam;

        // 1. Ambil semua soal dalam ujian ini
        $questions = $exam->questions; // Pastikan relasi questions ada di Model Exam

        // 2. Ambil semua jawaban yang sudah masuk
        $answers = ExamAnswer::where('exam_session_id', $session->id)->get()->keyBy('question_id');

        $totalPg = 0;
        $totalShort = 0;
        $totalEssay = 0;

        foreach ($questions as $q) {
            $answer = $answers->get($q->id);

            if (!$answer) {
                // LOGIKA POIN KOSONG (NULL)
                if (in_array($q->question_type, [QuestionType::SINGLE_CHOICE, QuestionType::MULTIPLE_CHOICE, QuestionType::TRUE_FALSE])) {
                    $totalPg += $exam->point_pg_null;
                } elseif ($q->question_type === QuestionType::SHORT_ANSWER) {
                    $totalShort += $exam->point_short_answer_null;
                } elseif ($q->question_type === QuestionType::ESSAY) {
                    $totalEssay += $exam->point_essay_null;
                }
                continue;
            }

            // LOGIKA POIN DARI JAWABAN ADA
            if (in_array($q->question_type, [QuestionType::SINGLE_CHOICE, QuestionType::MULTIPLE_CHOICE, QuestionType::TRUE_FALSE])) {
                $totalPg += $answer->score;
            } elseif ($q->question_type === QuestionType::SHORT_ANSWER) {
                $totalShort += $answer->score;
            } elseif ($q->question_type === QuestionType::ESSAY) {
                $totalEssay += $answer->score;
            }
        }

        $session->update([
            'score_pg' => $totalPg,
            'score_short_answer' => $totalShort,
            'score_essay' => $totalEssay,
            'total_score' => $totalPg + $totalShort + $totalEssay
        ]);
    }
}
