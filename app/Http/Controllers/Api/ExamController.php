<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StartExamRequest;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use Illuminate\Http\Request;
use Throwable;

class ExamController extends Controller
{
    protected $examRepo;

    public function __construct(ExamRepositoryInterface $examRepo)
    {
        $this->examRepo = $examRepo;
    }

    public function index(Request $request)
    {
        try {
            $status = $request->query('status', 'pending');
            $result = $this->examRepo->getActiveExams($status);
            return response()->success($result);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function getExamQuestions(Request $request)
    {
        try {
            $result = $this->examRepo->getExamQuestions($request->query('token', ''));
            return response()->success($result);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function startExamSession(StartExamRequest $request, Exam $exam)
    {
        try {
            $result = $this->examRepo->startExamSession($exam, $request->token);
            return response()->success($result);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function pauseExamSession(Request $request, Exam $exam)
    {
        try {
            $result = $this->examRepo->pauseExamSession($exam);
            return response()->success($result);
        } catch (Throwable $e) {
            throw $e;
        }
    }


    public function pauseExamSessions(Request $request)
    {
        try {
            $result = $this->examRepo->pauseExamSessions();
            return response()->success($result);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function getExamSession(Request $request, Exam $exam)
    {
        try {
            $result = $this->examRepo->getExamSession($exam);
            return response()->success($result);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function saveAnswer(Request $request, Exam $exam)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable', // Bisa ID pilihan (int), Array ID (multiple), atau String (essay)
            'is_doubtful' => 'required|boolean',
        ]);

        try {
            $token = $request->query('token');
            $result = $this->examRepo->saveAnswer($exam, $token, $request->all());
            return response()->success($result, "Jawaban berhasil disimpan");
        } catch (Throwable $e) {
            return response()->error($e->getMessage());
        }
    }

    public function getExamAnswers(Request $request, Exam $exam)
    {
        try {
            $token = $request->query('token');
            $result = $this->examRepo->getExamAnswers($exam, $token);
            return response()->success($result);
        } catch (Throwable $e) {
            return response()->error($e->getMessage());
        }
    }

    public function finalize(Request $request, Exam $exam)
{
    try {
        $token = $request->query('token');
        $isTimeout = $request->boolean('is_timeout', false);

        $result = $this->examRepo->finalizeExam($exam, $token, $isTimeout);

        return response()->success($result, $isTimeout
            ? "Waktu habis, jawaban otomatis dikirim."
            : "Ujian berhasil diselesaikan.");
    } catch (Throwable $e) {
        return response()->error($e->getMessage());
    }
}
}
