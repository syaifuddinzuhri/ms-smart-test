<?php

namespace App\Repositories;

use App\Enums\ExamSessionStatus;
use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Interfaces\AuthRepositoryInterface;
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

        if ($user->tokens()->exists()) {
            throw new Exception('Akun Anda sedang aktif di perangkat lain. Silakan logout terlebih dahulu.');
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

    public function getProfile(): UserResource
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            throw new Exception("Sesi berakhir, silakan login kembali");
        }

        return new UserResource($user->load('student.classroom.major'));
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
