<?php

namespace App\Filament\Student\Pages\Traits;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Models\ExamAnswer;
use App\Models\Question;
use App\Enums\QuestionType;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

trait HasExamStorage
{

    protected function syncAndValidateTimer()
    {
        $this->session->refresh();
        $this->exam->refresh();

        $now = now();

        // 1. Ambil data dari database
        $deadline = $this->session->expires_at; // Carbon or null
        $globalEndTime = $this->exam->end_time; // Carbon or null

        // 2. Logika Penentuan Target Waktu (Null-Safe)
        if (!$deadline && !$globalEndTime) {
            // Jika keduanya null (kasus darurat), gunakan durasi ujian dari sekarang
            $targetTime = $now->copy()->addMinutes($this->exam->duration);
        } elseif (!$deadline) {
            // Jika belum mulai (expires_at null), gunakan global end time
            $targetTime = $globalEndTime;
        } elseif (!$globalEndTime) {
            // Jika ujian tidak punya jadwal selesai (gerbang buka terus), gunakan expires_at
            $targetTime = $deadline;
        } else {
            // Jika keduanya ada, baru gunakan min() untuk cari yang paling cepat habis
            $targetTime = $deadline->min($globalEndTime);
        }

        // 3. Hitung Sisa Detik
        $remainingSeconds = $now->diffInSeconds($targetTime, false);

        // 4. Update Heartbeat & Sinkronisasi database untuk Admin
        // Kita simpan integer sisa detiknya agar Admin tidak perlu hitung manual lagi
        $this->session->update([
            'last_activity' => $now,
        ]);

        // 5. Jika Waktu Habis
        if ($remainingSeconds <= 0) {
            $this->durationInSeconds = 0;
            return $this->timeOut();
        }

        $this->durationInSeconds = (int) $remainingSeconds;
    }

    /**
     * Fungsi helper untuk menutup sesi jika jadwal benar-benar habis
     */
    protected function forceCloseSession($reason)
    {
        $this->session->update([
            'status' => ExamSessionStatus::COMPLETED,
            'finished_at' => now(),
            'token' => null,
            'system_id' => null,
        ]);

        Notification::make()->title('Sesi Berakhir')->body($reason)->danger()->persistent()->send();
        return redirect()->to('/student');
    }

    protected function validateSessionState()
    {
        $this->session->refresh();
        $this->exam->refresh();

        $isValid = true;
        $reason = '';

        if (!$this->session->token || !$this->session->system_id) {
            $isValid = false;
            $reason = 'Kredensial sesi ujian hilang atau tidak valid.';
        } elseif ($this->session->status !== ExamSessionStatus::ONGOING) {
            $isValid = false;
            $reason = 'Status ujian Anda saat ini adalah: ' . $this->session->status->getLabel();
        } elseif ($this->exam->status !== ExamStatus::ACTIVE) {
            $isValid = false;
            $reason = 'Ujian ini telah dinonaktifkan oleh pengawas.';
        }

        if (!$isValid) {
            Notification::make()
                ->title('Sesi Tidak Valid')
                ->body($reason)
                ->danger()
                ->send();

            return redirect()->to('/student');
        }

        return true;
    }
    public function saveAnswer($questionId = null, $moveNext = false)
    {
        $this->syncAndValidateTimer();

        if ($this->durationInSeconds <= 0) {
            return;
        }

        $validation = $this->validateSessionState();
        if ($validation !== true) {
            return $validation;
        }

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

        $examService = app(\App\Services\ExamService::class);

        if ($isAnswerEmpty && !$isDoubtful) {
            ExamAnswer::where('exam_session_id', $this->session->id)->where('question_id', $questionId)->delete();
            // Tetap sync meskipun dihapus (untuk update poin null)
            $examService->syncSessionScores($this->session);
        } else {
            DB::transaction(function () use ($questionId, $answerValue, $type, $isDoubtful, $examService) {
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

                // HITUNG SKOR JAWABAN INI
                $examService->gradeAnswer($answer);

                // UPDATE TOTAL KE SESSION
                $examService->syncSessionScores($this->session);
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
