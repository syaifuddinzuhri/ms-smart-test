<?php

namespace App\Filament\Student\Pages\Traits;

use App\Enums\QuestionType;
use App\Models\ExamQuestion;
use Livewire\Attributes\Computed;

trait HasExamQuestions
{
    #[Computed]
    public function allQuestions()
    {
        $qSeed = match ((int) $this->exam->random_question_type) {
            1 => $this->session->question_seed,
            2 => crc32($this->exam->id),
            default => null,
        };

        return ExamQuestion::query()
            ->where('exam_id', $this->exam->id)
            ->with([
                'question.options' => function ($query) {
                    if ($this->exam->random_option_type) {
                        $query->inRandomOrder($this->session->option_seed);
                    } else {
                        $query->orderBy('order', 'asc');
                    }
                }
            ])
            ->when(
                $qSeed,
                fn($q) => $q->inRandomOrder($qSeed), // Jika acak, gunakan seed
                fn($q) => $q->orderBy('order', 'asc') // Jika tidak acak, gunakan kolom 'order' di exam_questions
            )
            ->get()
            ->map(function ($examQuestion) {
                /**
                 * Agar kodenya tetap mudah digunakan di Blade,
                 * kita ambil object 'question' dan kita tempelkan info
                 * order dari pivot ke dalamnya.
                 */
                $question = $examQuestion->question;
                $question->pivot_order = $examQuestion->order; // Info nomor urut asli di ujian ini

                return $question;
            })
            ->filter();
    }

    #[Computed]
    public function pgQuestions()
    {
        return $this->allQuestions->filter(fn($q) => in_array($q->question_type, [
            QuestionType::SINGLE_CHOICE,
            QuestionType::MULTIPLE_CHOICE,
            QuestionType::TRUE_FALSE
        ]))->values();
    }

    #[Computed]
    public function essayQuestions()
    {
        return $this->allQuestions->filter(fn($q) => in_array($q->question_type, [
            QuestionType::SHORT_ANSWER,
            QuestionType::ESSAY
        ]))->values();
    }
}
