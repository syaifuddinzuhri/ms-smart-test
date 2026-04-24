<?php

namespace App\Repositories;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Enums\ExamTokenType;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Classroom;
use App\Models\Exam;
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

        $tokenHash = hash('sha256', $token);
        $session = ExamSession::where('token', $tokenHash)->first();

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
        ExamSession::where('user_id', auth_api()->id)
            ->where('exam_id', $exam->id)
            ->where('status', ExamSessionStatus::ONGOING)
            ->update([
                'token' => null,
                'system_id' => null,
                'status' => ExamSessionStatus::PAUSE
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
}
