<?php

namespace App\Interfaces;

use App\Models\Exam;

interface ExamRepositoryInterface
{
    public function getActiveExams(string $status);
    public function getExamQuestions(string $token);
    public function startExamSession(Exam $exam, string $token);
    public function pauseExamSession(Exam $exam);
    public function getExamSession(Exam $exam);
    public function saveAnswer(Exam $exam, string $token, array $data);
    public function getExamAnswers(Exam $exam, string $token);
    public function finalizeExam(Exam $exam, string $token, bool $isTimeout = false);
}
