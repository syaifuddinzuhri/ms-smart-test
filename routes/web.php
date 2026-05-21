<?php

use App\Models\ExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$mainDomain = str_replace(['http://', 'https://'], '', config('app.app_domain'));

Route::domain($mainDomain)->group(function () {
    Route::get('/', function () {
        return redirect()->to('http://' . config('app.student_domain'));
    });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');


Route::get('/test/exam-questions', function (Request $request) {
    $examId = $request->query('exam_id');

    if (!$examId) {
        return response()->json(['message' => 'exam_id required'], 422);
    }

    $questions = ExamQuestion::where('exam_id', $examId)
        ->with([
            'question.options',
            'question.attachments',
        ])
        ->orderBy('order', 'asc')
        ->get()
        ->map(function ($examQuestion) {
            $question = $examQuestion->question;
            $question->pivot_order = $examQuestion->order;
            return $question;
        })
        ->filter();

    return response()->json([
        'success' => true,
        'data' => $questions
    ]);
});

Route::get('/pandoc', function () {
    $os = PHP_OS;
    $pandocPath = 'pandoc';

    if (strtoupper(substr($os, 0, 3)) === 'WIN') {
        $winCheck = shell_exec("where pandoc");
        $pandocPath = $winCheck ? 'pandoc' : 'C:\Program Files\Pandoc\pandoc.exe';
    } elseif ($os === 'Darwin') {
        $macPaths = ['/opt/homebrew/bin/pandoc', '/usr/local/bin/pandoc'];
        foreach ($macPaths as $path) {
            if (file_exists($path)) {
                $pandocPath = $path;
                break;
            }
        }
    } else {
        $linuxCheck = trim(shell_exec("which pandoc"));
        $pandocPath = !empty($linuxCheck) ? $linuxCheck : '/usr/bin/pandoc';
    }

    $output = shell_exec(escapeshellarg($pandocPath) . " -v 2>&1");
    $exists = file_exists($pandocPath) || !empty(shell_exec("which pandoc") ?? shell_exec("where pandoc"));

    return response()->json([
        'detected_os' => $os,
        'target_path' => $pandocPath,
        'is_file_exists' => file_exists($pandocPath),
        'can_execute' => !empty($output) && !str_contains($output, 'not found'),
        'version_output' => $output ? trim($output) : 'Gagal menjalankan command. Cek permissions atau disable_functions di php.ini.',
    ]);
});
