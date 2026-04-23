<?php

namespace App\Repositories;

use App\Enums\ExamSessionStatus;
use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Interfaces\AuthRepositoryInterface;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthRepository implements AuthRepositoryInterface
{
    public function login(array $credentials): array
    {
        $user = User::where('username', $credentials['username'])->where('role', UserRole::STUDENT)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Kredensial yang diberikan salah.'],
            ]);
        }

        $lifetime = config('session.lifetime');
        $threshold = now()->subMinutes($lifetime)->getTimestamp();

        DB::table('sessions')->where('last_activity', '<', $threshold)->delete();

        $hasWebSession = DB::table('sessions')
            ->where('user_id', $user->id)
            ->exists();

        $hasMobileToken = $user->tokens()->exists();

        if ($hasWebSession || $hasMobileToken) {
            throw new \Exception('Akun Anda sedang aktif di perangkat lain (Web atau Mobile). Silakan logout terlebih dahulu.');
        }

        $token = DB::transaction(function () use ($user) {
            ExamSession::where('user_id', $user->id)
                ->where('status', '!=', ExamSessionStatus::COMPLETED)
                ->update([
                    'token' => null,
                    'system_id' => null,
                    'status' => DB::raw("CASE
                    WHEN status = '" . ExamSessionStatus::ONGOING->value . "' THEN '" . ExamSessionStatus::PAUSE->value . "'
                    ELSE status
                END")
                ]);

            $user->tokens()->delete();
            $token = $user->createToken('auth_token_mobile')->plainTextToken;
            return $token;
        });

        return [
            'user' => new UserResource($user->load(['student.classroom.major'])),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function getProfile(): array
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            throw new Exception("Sesi berakhir, silakan login kembali");
        }

        $exams_done = ExamSession::where('user_id', $user->id)
            ->where('status', ExamSessionStatus::COMPLETED)
            ->count();

        $startedExamIds = ExamSession::where('user_id', $user->id)->pluck('exam_id');
        $exams_pending = Exam::whereHas('classrooms', function ($q) use ($user) {
            $q->where('classroom_id', $user->student?->classroom_id);
        })
            ->whereNotIn('id', $startedExamIds)
            ->count();

        $highest_score = ExamSession::where('user_id', $user->id)
            ->where('status', ExamSessionStatus::COMPLETED)
            ->max('total_score') ?? 0;

        $average_score = ExamSession::where('user_id', $user->id)
            ->where('status', ExamSessionStatus::COMPLETED)
            ->avg('total_score') ?? 0;


        return [
            'stats' => [
                'exams_done' => $exams_done,
                'exams_pending' => $exams_pending,
                'highest_score' => $highest_score,
                'average_score' => $average_score
            ],
            'user' => new UserResource($user->load('student.classroom.major'))
        ];
    }

    public function logout(): bool
    {
        $user = Auth::guard('api')->user();
        if ($user) {
            $user->tokens()->delete();
        }
        return false;
    }
}
