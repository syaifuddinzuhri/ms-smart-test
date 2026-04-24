<?php

namespace App\Repositories;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Enums\ExamTokenType;
use App\Enums\QuestionType;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamClassroom;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Services\ExamService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ExamRepository implements ExamRepositoryInterface
{
    public function getActiveExams(string $status = 'pending')
    {
        $user = auth_api();
        $classroomId = $user->student?->classroom_id;
        $now = Carbon::now();

        $query = Exam::query()
            ->with(['category', 'subject'])
            ->where('is_lock', true)
            ->whereHas('classrooms', function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->whereHas('examQuestions');

        match ($status) {
            'completed' => $query->whereHas('sessions', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('status', ExamSessionStatus::COMPLETED);
            }),

            'active' => $query->whereHas('sessions', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('status', '!=', ExamSessionStatus::COMPLETED);
            }),
            'pending' => $query->whereDoesntHave('sessions', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->where('status', ExamStatus::ACTIVE)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now),

            default => $query
        };

        $query->addSelect([
            'finished_at' => ExamSession::select('finished_at')
                ->whereColumn('exam_id', 'exams.id')
                ->where('user_id', $user->id)
                ->latest()
                ->take(1),
            'student_score' => ExamSession::select('total_score')
                ->whereColumn('exam_id', 'exams.id')
                ->where('user_id', $user->id)
                ->latest()
                ->take(1),
            'passing_grade' => ExamClassroom::select('min_total_score')
                ->whereColumn('exam_id', 'exams.id')
                ->where('classroom_id', $classroomId)
                ->take(1),
            'target_classroom' => Classroom::query()
                ->join('majors', 'classrooms.major_id', '=', 'majors.id')
                ->where('classrooms.id', $classroomId)
                ->selectRaw("CONCAT(classrooms.name, ' - ', majors.name)")
                ->take(1),
        ]);

        $query->with([
            'sessions' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ]);

        return $query->get();
    }

    public function getExamQuestions(string $token)
    {
        if (!$token)
            throw new Exception('Token invalid');

        $session = ExamSession::where('token', $token)->first();

        if (!$session)
            throw new Exception("Token salah / Sesi telah berakhir. Silahkan masukkan token ulang");

        $exam = Exam::find($session->exam_id);

        $orderSeed = match ((int) $exam->random_question_type) {
            1 => $session->question_seed,
            2 => crc32($exam->id),
            default => null,
        };

        $results = ExamQuestion::query()
            ->where('exam_id', $exam->id)
            ->with([
                'question.options' => function ($query) use ($exam, $session) {
                    if ($exam->random_option_type) {
                        $query->inRandomOrder($session->option_seed);
                    } else {
                        $query->orderBy('order', 'asc');
                    }
                }
            ])
            ->when(
                $orderSeed,
                fn($q) => $q->inRandomOrder($orderSeed),
                fn($q) => $q->orderBy('order', 'asc')
            )
            ->get()
            ->map(function ($examQuestion) {
                $question = $examQuestion->question;
                $question->pivot_order = $examQuestion->order;
                return $question;
            })
            ->filter();

        return $results;
    }

    public function startExamSession(Exam $exam, string $token)
    {
        $user = auth_api();

        $activeExamSession = ExamSession::where('user_id', $user->id)
            ->whereIn('status', [ExamSessionStatus::ONGOING, ExamSessionStatus::PAUSE])
            ->where('exam_id', '!=', $exam->id)
            ->exists();

        if ($activeExamSession) {
            throw new Exception("Masih terdapat ujian yang berlangsung");
        }

        $session = ExamSession::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();

        $requiredType = $session ? ExamTokenType::RELOGIN : ExamTokenType::ACCESS;

        $validToken = ExamToken::where('exam_id', $exam->id)
            ->where('token', $token)
            ->where('type', $requiredType)
            ->where('is_active', true)
            ->where('expired_at', '>', now())
            ->first();

        if (!$validToken) {
            throw new Exception(
                $session
                    ? 'Token kadaluarsa / Sesi Anda terdeteksi sudah berjalan. Silahkan masukkan token akses masuk ulang dari pengawas.'
                    : 'Token akses salah, kadaluarsa, atau tidak sesuai tipe.'
            );
        }

        $maxAllowed = ($requiredType === ExamTokenType::ACCESS)
            ? config('exam_token.max_usage', 50)
            : 1;

        if ($validToken->used_count >= $maxAllowed) {
            $validToken->update(['is_active' => false]);
            throw new Exception("Kuota penggunaan token akses ini sudah habis.");
        }

        $tokenSession = DB::transaction(function () use ($validToken, $maxAllowed, $user, $exam) {
            $rowsAffected = ExamToken::where('id', $validToken->id)
                ->where('is_active', true)
                ->where('used_count', '<', $maxAllowed)
                ->increment('used_count', 1, ['used_at' => now()]);

            if ($rowsAffected === 0) {
                throw new Exception("Kuota penggunaan token akses ini sudah habis.");
            }

            $validToken->refresh();
            if ($validToken->is_single_use || $validToken->used_count >= $maxAllowed) {
                $validToken->update(['is_active' => false]);
            }

            $token = app(ExamService::class)->initializeExamSession($user, $exam);
            return $token;
        });

        return [
            'token' => $tokenSession
        ];
    }

    public function pauseExamSession(Exam $exam)
    {
        $session =  ExamSession::where('user_id', auth_api()->id)
            ->where('exam_id', $exam->id)
            ->where('status', ExamSessionStatus::ONGOING)
            ->first();

        $logs = $session->violation_log ?? [];

        $logs[] = [
            'time' => now()->toDateTimeString(),
            'reason' => 'Sistem mendeteksi keluar dari halaman ujian',
            'step' => null,
            'tab' => null,
            'ip' => request()->ip(),
        ];

        ExamSession::where('user_id', auth_api()->id)
            ->where('exam_id', $exam->id)
            ->where('status', ExamSessionStatus::ONGOING)
            ->update([
                'token' => null,
                'system_id' => null,
                'status' => ExamSessionStatus::PAUSE,
                'last_violation_at' => now(),
                'violation_count' => ($session->violation_count ?? 0) + 1,
                'violation_log' => $logs,
            ]);
    }

    public function pauseExamSessions()
    {
        ExamSession::where('user_id', auth_api()->id)
            ->where('status', ExamSessionStatus::ONGOING)
            ->update([
                'token' => null,
                'system_id' => null,
                'status' => ExamSessionStatus::PAUSE
            ]);
    }

    public function getExamSession(Exam $exam)
    {
        return ExamSession::with(['exam.subject', 'exam.category'])
            ->where('user_id', auth_api()->id)
            ->where('exam_id', $exam->id)
            ->first();
    }

    public function saveAnswer(Exam $exam, string $token, array $data)
    {
        $user = auth_api();
        $questionId = $data['question_id'];
        $answerValue = $data['answer']; // Input dari Mobile
        $isDoubtful = $data['is_doubtful'];

        // 1. Ambil Sesi berdasarkan Token
        $session = ExamSession::where('token', $token)
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->first();

        if (!$session || $session->status !== ExamSessionStatus::ONGOING) {
            throw new Exception("Sesi tidak valid atau telah berakhir.");
        }

        // 2. Cek Kedaluwarsa Waktu
        if (now()->greaterThan($session->expires_at)) {
            throw new Exception("Waktu pengerjaan telah habis.");
        }

        // 3. Ambil Meta Data Soal
        $q = \App\Models\Question::findOrFail($questionId);
        $type = $q->question_type;

        // Cek apakah jawaban kosong (Null)
        $isAnswerEmpty = is_array($answerValue)
            ? empty($answerValue)
            : (trim(strip_tags((string) $answerValue)) === '');

        $examService = app(ExamService::class);

        // 4. Dapatkan Skor Lama (untuk hitung selisih/incremental)
        $oldAnswer = ExamAnswer::where('exam_session_id', $session->id)
            ->where('question_id', $questionId)
            ->first();

        if (!$oldAnswer) {
            $oldScore = match (true) {
                $q->isPg() => -(float) $exam->point_pg_null,
                $q->isShortAnswer() => -(float) $exam->point_short_answer_null,
                default => -(float) $exam->point_essay_null
            };
        } else {
            $oldScore = (float) $oldAnswer->score;
        }

        // 5. Eksekusi Database Transaction
        DB::transaction(function () use ($session, $exam, $q, $questionId, $answerValue, $isDoubtful, $isAnswerEmpty, $oldScore, $examService, $type) {

            $newScore = 0;

            // JIKA JAWABAN DIHAPUS & TIDAK RAGU-RAGU
            if ($isAnswerEmpty && !$isDoubtful) {
                $newScore = match (true) {
                    $q->isPg() => -(float) $exam->point_pg_null,
                    $q->isShortAnswer() => -(float) $exam->point_short_answer_null,
                    default => -(float) $exam->point_essay_null
                };
                ExamAnswer::where('exam_session_id', $session->id)->where('question_id', $questionId)->delete();
            }

            // JIKA ADA JAWABAN ATAU RAGU-RAGU
            else {
                $answer = ExamAnswer::updateOrCreate(
                    ['exam_session_id' => $session->id, 'question_id' => $questionId],
                    [
                        'answer_text' => in_array($type, [QuestionType::ESSAY, QuestionType::SHORT_ANSWER]) ? $answerValue : null,
                        'is_doubtful' => $isDoubtful,
                    ]
                );

                // Jika PG (Single atau Multiple Choice)
                if ($q->isPg()) {
                    // Flutter mengirim ID opsi (int) atau List ID (array)
                    $optionIds = is_array($answerValue) ? $answerValue : ($answerValue ? [$answerValue] : []);
                    $answer->selectedOptions()->sync($optionIds);
                }

                // Hitung Skor Baru
                if ($q->isEssay()) {
                    $newScore = is_null($answer->is_correct) ? 0 : (float) $answer->score;
                } else {
                    $newScore = $examService->calculateScore($answer) ?? 0;
                }

                $answer->update([
                    'score' => $newScore,
                    'is_correct' => $q->isEssay() ? null : ($newScore > 0)
                ]);
            }

            // 6. Sinkronisasi Skor ke Tabel ExamSession (Incremental Update)
            $examService->updateIncrementalScore($session, $type, $oldScore, $newScore);

            // Update Heartbeat
            $session->update(['last_activity' => now()]);
        });

        return [
            'is_answered' => !$isAnswerEmpty,
            'is_doubtful' => $isDoubtful
        ];
    }

    public function getExamAnswers(Exam $exam, string $token)
    {
        $user = auth_api();

        // 1. Cari Sesi
        $session = ExamSession::where('token', $token)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) throw new Exception("Sesi tidak ditemukan.");

        // 2. Ambil Semua Jawaban
        return ExamAnswer::where('exam_session_id', $session->id)
            ->with('selectedOptions:id') // Hanya ambil ID opsi saja
            ->get()
            ->map(function ($answer) {
                return [
                    'question_id' => $answer->question_id,
                    'answer_text' => $answer->answer_text,
                    'is_doubtful' => (bool) $answer->is_doubtful,
                    // Ambil list ID pilihan yang dipilih (untuk PG/Multiple Choice)
                    'selected_option_ids' => $answer->selectedOptions->pluck('id')->toArray(),
                ];
            });
    }

    public function finalizeExam(Exam $exam, string $token, bool $isTimeout = false)
    {
        $user = auth_api();

        // 1. Cari Sesi
        $session = ExamSession::where('token', $token)
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->first();

        if (!$session) {
            throw new Exception("Sesi tidak ditemukan atau token tidak valid.");
        }

        // 2. Cegah double submit
        if ($session->status === ExamSessionStatus::COMPLETED) {
           throw new Exception("Sesi ujian ini telah diselesaikan. Silahkan kembali ke halaman utama.");
        }

        // 3. Proteksi Waktu (Sesuai Logika Filament Anda)
        // Jika waktu server sekarang > end_time ujian + 5 menit toleransi
        if (now()->gt($exam->end_time->addMinutes(5))) {
            $isTimeout = true;
        }

        // 4. Eksekusi Finalisasi dalam Transaksi
        DB::transaction(function () use ($session, $exam) {
            $examService = app(ExamService::class);

            // SYNC SKOR AKHIR (PENTING!)
            // Memastikan total_score di sesi sama dengan akumulasi di tabel exam_answers
            $examService->syncSessionScores($session);

            // UPDATE STATUS SESI
            $session->update([
                'token' => null,     // Hapus token agar tidak bisa digunakan lagi
                'system_id' => null, // Melepas lock perangkat
                'status' => ExamSessionStatus::COMPLETED,
                'finished_at' => now(),
                'last_activity' => now(),
            ]);
        });

        return [
            'exam_title' => $exam->title,
            'finished_at' => now()->toDateTimeString(),
            'is_timeout' => $isTimeout
        ];
    }
}
