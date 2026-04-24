<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamController;
use Illuminate\Support\Facades\Route;

$apiDomain = env('API_DOMAIN', 'api.ms-smart-test.test');

Route::domain($apiDomain)->group(function () {

    Route::prefix('v1')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('/login', [AuthController::class, 'login']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('auth')->group(function () {
                Route::get('/me', [AuthController::class, 'me']);
                Route::post('/logout', [AuthController::class, 'logout']);
            });

            Route::prefix('exams')->group(function () {
                Route::get('/', [ExamController::class, 'index']);
                Route::get('/questions', [ExamController::class, 'getExamQuestions']);
                Route::post('/pause-sessions', [ExamController::class, 'pauseExamSessions']);
                Route::post('/{exam}/start-session', [ExamController::class, 'startExamSession']);
                Route::post('/{exam}/pause-session', [ExamController::class, 'pauseExamSession']);
                Route::get('/{exam}/session', [ExamController::class, 'getExamSession']);
                Route::post('/{exam}/save-answer', [ExamController::class, 'saveAnswer']);
                Route::get('/{exam}/answers', [ExamController::class, 'getExamAnswers']);
                Route::post('/exams/{exam}/finalize', [ExamController::class, 'finalize']);
            });
        });
    });
});
