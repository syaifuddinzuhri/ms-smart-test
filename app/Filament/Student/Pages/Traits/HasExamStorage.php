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

        Notification::make()->title('Sesi Berakhir')->body($reason)->danger()->send();
        return redirect()->to('/');
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

            return redirect()->to('/');
        }

        return true;
    }
    public function saveAnswer($questionId = null, $moveNext = false)
    {
        // 1. Validasi Waktu dan Sesi
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

        // 2. Ambil Meta Data Soal
        $q = Question::find($questionId);
        $type = $q->question_type;

        // 3. Ambil Input Data
        $answerValue = $this->data["q{$questionId}"] ?? null;
        $isDoubtful = in_array($questionId, $this->doubtfulQuestions);
        $isAnswerEmpty = is_array($answerValue)
            ? empty($answerValue)
            : (trim(strip_tags((string) $answerValue)) === '');

        $examService = app(\App\Services\ExamService::class);

        // 4. Dapatkan "Skor Lama" sebelum perubahan
        // Ini penting untuk menghitung selisih (diff)
        $oldAnswer = ExamAnswer::where('exam_session_id', $this->session->id)
            ->where('question_id', $questionId)
            ->first();

        if (!$oldAnswer) {
            // Jika belum pernah dijawab, status siswa saat ini adalah dapet "Point Null"
            $oldScore = match (true) {
                $q->isPg() => -(float) $this->exam->point_pg_null,
                $q->isShortAnswer() => -(float) $this->exam->point_short_answer_null,
                default => -(float) $this->exam->point_essay_null
            };
        } else {
            $oldScore = (float) $oldAnswer->score;
        }

        // 5. Proses Transaksi Penilaian
        DB::transaction(function () use ($questionId, $answerValue, $type, $isDoubtful, $examService, $isAnswerEmpty, $oldScore, $q) {

            $newScore = 0;

            // CASE A: Jawaban dikosongkan (dan tidak ragu-ragu) -> Kembali ke Point Null
            if ($isAnswerEmpty && !$isDoubtful) {
                $newScore = match (true) {
                    $q->isPg() => -(float) $this->exam->point_pg_null,
                    $q->isShortAnswer() => -(float) $this->exam->point_short_answer_null,
                    default => -(float) $this->exam->point_essay_null
                };

                ExamAnswer::where('exam_session_id', $this->session->id)
                    ->where('question_id', $questionId)
                    ->delete();
            }

            // CASE B: Ada jawaban atau status ragu-ragu
            else {
                $answer = ExamAnswer::updateOrCreate(
                    ['exam_session_id' => $this->session->id, 'question_id' => $questionId],
                    [
                        'answer_text' => in_array($type, [QuestionType::ESSAY, QuestionType::SHORT_ANSWER]) ? $answerValue : null,
                        'is_doubtful' => $isDoubtful,
                    ]
                );

                // Sync pilihan jika PG
                if ($q->isPg()) {
                    $optionIds = is_array($answerValue) ? $answerValue : ($answerValue ? [$answerValue] : []);
                    $answer->selectedOptions()->sync($optionIds);
                }

                if ($q->isEssay()) {
                    // Jika Essay baru diisi (is_correct null), skor = 0 (artinya hutang pinalti lunas)
                    // Jika sudah dikoreksi, gunakan skor yang ada.
                    $newScore = is_null($answer->is_correct) ? 0 : (float) $answer->score;
                } else {
                    $newScore = $examService->calculateScore($answer) ?? 0;
                }

                $answer->update([
                    'score' => $newScore,
                    'is_correct' => $q->isEssay() ? null : ($newScore > 0)
                ]);
            }

            // 6. Update Sesi secara Incremental (SANGAT CEPAT)
            $examService->updateIncrementalScore($this->session, $type, $oldScore, $newScore);
        });

        // 7. Navigasi
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
