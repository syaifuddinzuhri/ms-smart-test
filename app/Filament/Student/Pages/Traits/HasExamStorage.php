<?php

namespace App\Filament\Student\Pages\Traits;

use App\Models\ExamAnswer;
use App\Models\Question;
use App\Enums\QuestionType;
use Illuminate\Support\Facades\DB;

trait HasExamStorage
{
    public function saveAnswer($questionId = null, $moveNext = false)
    {
        $questionId = $questionId ?: $this->currentQuestionId;
        if (!$questionId)
            return;

        $type = ($questionId === $this->currentQuestionId)
            ? $this->currentQuestion->question_type
            : Question::where('id', $questionId)->value('question_type');

        $answerValue = $this->data["q{$questionId}"] ?? null;
        $isDoubtful = in_array($questionId, $this->doubtfulQuestions);
        $isAnswerEmpty = is_array($answerValue)
            ? empty($answerValue)
            : (trim(strip_tags((string) $answerValue)) === '');

        if ($isAnswerEmpty && !$isDoubtful) {
            ExamAnswer::where('exam_session_id', $this->session->id)->where('question_id', $questionId)->delete();
        } else {
            DB::transaction(function () use ($questionId, $answerValue, $type, $isDoubtful) {
                $answer = ExamAnswer::updateOrCreate(
                    ['exam_session_id' => $this->session->id, 'question_id' => $questionId],
                    [
                        'answer_text' => in_array($type, [QuestionType::ESSAY, QuestionType::SHORT_ANSWER]) ? $answerValue : null,
                        'is_doubtful' => $isDoubtful,
                    ]
                );

                if (in_array($type, [QuestionType::SINGLE_CHOICE, QuestionType::MULTIPLE_CHOICE, QuestionType::TRUE_FALSE])) {
                    $optionIds = is_array($answerValue) ? $answerValue : ($answerValue ? [$answerValue] : []);
                    $answer->selectedOptions()->sync($optionIds);
                }
            });
        }

        // Navigasi HANYA jika diperintahkan (Tombol Simpan & Lanjutkan)
        if ($moveNext) {
            $this->next();
        }
    }

    public function toggleDoubt($questionId = null)
    {
        // Jika $questionId null (dari klik tombol bawah), gunakan ID aktif saat ini
        $id = $questionId ?: $this->currentQuestionId;

        if (!$id)
            return;

        // Pastikan ID bersih (jika ada prefix q)
        $id = str_replace('q', '', $id);

        if (in_array($id, $this->doubtfulQuestions)) {
            $this->doubtfulQuestions = array_values(array_diff($this->doubtfulQuestions, [$id]));
        } else {
            $this->doubtfulQuestions[] = $id;
        }

        // Simpan ke database
        $this->saveAnswer($id, false);
    }

    protected function getSummaryCounts(): array
    {
        $answered = 0;
        $doubtful = count($this->doubtfulQuestions);
        $unanswered = 0;

        $allQuestions = $this->pgQuestions->concat($this->essayQuestions);

        foreach ($allQuestions as $q) {
            $status = $this->getQuestionStatus($q->id);

            if ($status === 'unanswered') {
                $unanswered++;
            } else {
                $answered++;
            }
        }

        return [
            'answered' => $answered,
            'doubtful' => $doubtful,
            'unanswered' => $unanswered,
        ];
    }
}
