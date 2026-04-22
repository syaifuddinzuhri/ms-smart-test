<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExamSessionStatus;
use App\Enums\QuestionType;
use App\Filament\Student\Pages\Traits\HasExamNavigation;
use App\Filament\Student\Pages\Traits\HasExamQuestions;
use App\Filament\Student\Pages\Traits\HasExamStorage;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Services\ExamService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class StartTest extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;
    use HasExamQuestions;
    use HasExamNavigation;
    use HasExamStorage;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Simulasi Ujian Online';
    protected static string $view = 'filament.student.pages.start-test';

    public ?array $data = [];
    public $activeTab = 'pg', $currentStep = 1;
    public int $totalPG = 0, $totalEssay = 0;
    public $doubtfulQuestions = [], $durationInSeconds = 0;
    public bool $isLocked = false;
    public ?string $token = null;
    public $sessionId;
    public ?Exam $exam = null;
    public ?ExamSession $session = null;

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.student.components.hide-nav-css');
    }

    public function mount()
    {
        $this->token = request()->query('token');
        $tokenHash = hash('sha256', $this->token);

        // Cari session pertama kali
        $this->session = ExamSession::where('token', $tokenHash)->first();

        // 1. Validasi Keberadaan Session
        if (!$this->session) {
            return redirect()->to('/');
        }

        // Simpan ID untuk request selanjutnya
        $this->sessionId = $this->session->id;

        // 2. Validasi Keberadaan Exam
        $this->exam = Exam::find($this->session->exam_id);
        if (!$this->exam) {
            return redirect()->to('/');
        }

        // 3. Logika Status Pause
        if ($this->session->status === ExamSessionStatus::PAUSE) {
            $this->isLocked = true;
        }

        $this->totalPG = $this->pgQuestions->count();
        $this->totalEssay = $this->essayQuestions->count();
        if ($this->totalPG === 0 && $this->totalEssay > 0)
            $this->activeTab = 'essay';

        $this->syncAndValidateTimer();

        // Load Initial Data
        $savedAnswers = ExamAnswer::where('exam_session_id', $this->session->id)->with('selectedOptions')->get();
        foreach ($savedAnswers as $saved) {
            if ($saved->answer_text) {
                $this->data["q{$saved->question_id}"] = $saved->answer_text;
            } else {
                $opts = $saved->selectedOptions->pluck('id')->toArray();
                $this->data["q{$saved->question_id}"] = count($opts) > 1 ? $opts : ($opts[0] ?? null);
            }
            if ($saved->is_doubtful)
                $this->doubtfulQuestions[] = $saved->question_id;
        }
        $this->form->fill($this->data);
    }

    public function lockExam($reason = 'Sistem mendeteksi perpindahan jendela/tab'): void
    {
        if (!isProduction())
            return;

        $this->session->refresh();

        $logs = $this->session->violation_log ?? [];

        $logs[] = [
            'time' => now()->toDateTimeString(),
            'reason' => $reason,
            'step' => $this->currentStep,
            'tab' => $this->activeTab,
            'ip' => request()->ip(),
        ];

        $updateData = [
            'status' => ExamSessionStatus::PAUSE,
            'last_violation_at' => now(),
            'violation_count' => ($this->exam->violation_count ?? 0) + 1,
            'violation_log' => $logs,
        ];

        $this->session->update($updateData);
        $this->isLocked = true;
    }

    public function updateRemainingTime(): int
    {
        if ($this->session) {
            $this->session->refresh();
            $now = now();
            // Deadline pengerjaan (jatah individu)
            $deadline = $this->session->expires_at;
            // Deadline jadwal (gerbang global)
            $globalLimit = $this->exam->end_time;

            // Ambil mana yang lebih dulu habis
            $target = $deadline->min($globalLimit);

            $serverRemaining = $now->diffInSeconds($target, false);
            $actualRemaining = max(0, (int) $serverRemaining);

            return $actualRemaining;
        }

        return 0;
    }

    public function timeOut(): void
    {
        $lastCurrentQuestionId = $this->currentQuestionId;

        if ($lastCurrentQuestionId) {
            $this->saveAnswer($lastCurrentQuestionId, false);
        }

        $this->dispatch('close-modal', id: '*');

        $this->submit(true);
    }

    public function getQuestionStatus($questionId)
    {
        $fieldName = "q{$questionId}";
        $answer = $this->data[$fieldName] ?? null;

        $isAnswered = false;
        if (is_array($answer)) {
            $isAnswered = count($answer) > 0;
        } else {
            $isAnswered = !empty($answer) && trim(strip_tags($answer)) !== '';
        }

        if (in_array($questionId, $this->doubtfulQuestions))
            return 'doubtful';
        if ($isAnswered)
            return 'answered';
        return 'unanswered';
    }

    public function isAllAnswered(): bool
    {
        $totalRequired = $this->totalPG + $this->totalEssay;
        $answeredCount = 0;

        foreach ($this->data as $key => $value) {
            if (str_starts_with($key, 'q')) {
                if (is_array($value) ? count($value) > 0 : !empty(trim(strip_tags($value)))) {
                    $answeredCount++;
                }
            }
        }

        return $answeredCount >= $totalRequired;
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    ...$this->pgQuestions->map(fn($q, $i) => $this->buildQuestionField($q, $i + 1, 'pg')),
                ]),
                Group::make([
                    ...$this->essayQuestions->map(fn($q, $i) => $this->buildQuestionField($q, $i + 1, 'essay')),
                ]),
            ])
            ->statePath('data');
    }

    protected function buildQuestionField($q, $step, $tab)
    {
        $name = "q{$q->id}";
        $isEssay = in_array($tab, ['essay']);

        if ($tab === 'pg') {
            $input = ($q->question_type === QuestionType::MULTIPLE_CHOICE) ? CheckboxList::make($name) : Radio::make($name);

            $letters = range('A', 'Z');

            $input->options($q->options->mapWithKeys(function ($opt, $index) use ($letters, $q) {
                $char = $letters[$index] ?? '';

                if ($q->question_type === QuestionType::MULTIPLE_CHOICE || $q->question_type === QuestionType::SINGLE_CHOICE) {
                    return [
                        $opt->id => new HtmlString(
                            "<div class='flex items-start gap-2 text-gray-700'>
                                <span class='font-bold flex-shrink-0'>{$char}.</span>
                                <div class='prose prose-sm max-w-none inline-block soal-content'>{$opt->text}</div>
                            </div>"
                        )
                    ];
                }

                return [
                    $opt->id => new HtmlString(
                        "<div class='prose prose-sm max-w-none inline-block text-gray-700 soal-content'>{$opt->text}</div>"
                    )
                ];
            })->toArray());
        } else {
            $input = ($q->question_type === QuestionType::SHORT_ANSWER) ? TextInput::make($name) : RichEditor::make($name)
                ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList']);
        }

        return Group::make([
            Placeholder::make("text_{$name}")
                ->label('')
                ->content(
                    new HtmlString(
                        "<div class='prose max-w-none text-gray-800 soal-content'>{$q->question_text}</div>"
                    )
                ),
            $input->label($isEssay ? 'Jawaban Anda:' : 'Pilih jawaban:')
                ->extraAttributes(['class' => 'mt-4']),
        ])->visible(fn() => $this->activeTab === $tab && $this->currentStep === $step)
            ->key("group_{$tab}_{$q->id}");
    }


    public function submitAction(): Action
    {
        return Action::make('submit')
            ->label('Hentikan & Kirim Hasil')
            ->icon('heroicon-m-paper-airplane')
            ->color('info')
            ->size('md')
            ->extraAttributes([
                'class' => 'w-full md:w-auto justify-center',
            ])
            ->requiresConfirmation()
            ->modalHeading('Kirim Jawaban Ujian?')
            ->modalDescription(fn() => new HtmlString("
            <div class='space-y-4'>
                <p>Pastikan semua jawaban sudah benar. Setelah dikirim, Anda tidak dapat mengubah jawaban lagi.</p>

                <div class='bg-gray-50 p-4 rounded-lg border border-gray-200'>
                    <p class='text-sm font-bold text-gray-700 mb-2'>Rangkuman Pengerjaan:</p>
                    <div class='grid grid-cols-1 gap-2 text-sm'>
                        <div class='flex justify-between'>
                            <span class='text-gray-600'>Total Soal:</span>
                            <span class='font-semibold'>" . ($this->totalPG + $this->totalEssay) . "</span>
                        </div>
                        <div class='flex justify-between text-green-600'>
                            <span>Sudah Dijawab:</span>
                            <span class='font-semibold'>{$this->getSummaryCounts()['answered']}</span>
                        </div>
                        <div class='flex justify-between text-orange-600'>
                            <span>Ragu-Ragu:</span>
                            <span class='font-semibold'>{$this->getSummaryCounts()['doubtful']}</span>
                        </div>
                        <div class='flex justify-between text-red-600'>
                            <span>Belum Dijawab:</span>
                            <span class='font-semibold'>{$this->getSummaryCounts()['unanswered']}</span>
                        </div>
                    </div>
                </div>
            </div>
        "))
            ->form([
                Checkbox::make('confirm_submit')
                    ->label('Saya menyatakan bahwa saya telah memeriksa kembali semua jawaban dan setuju untuk mengakhiri ujian ini.')
                    ->required(),
            ])
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->modalCancelActionLabel('Batal')
            ->modalIcon('heroicon-o-check-circle')
            ->modalAlignment(Alignment::Center)
            ->action(function (array $data) {
                $this->submit();
            });
    }

    public function submit(bool $isTimeout = false)
    {
        $this->dispatch('prepare-navigation');
        $this->form->getState();

        $this->exam->refresh();
        $this->session->refresh();

        if ($this->session->status === ExamSessionStatus::COMPLETED) {
            return;
        }

        // Proteksi tambahan: Jika end_time ujian sudah lewat > 5 menit (toleransi),
        // paksa submit meskipun JS belum mengirim timeout.
        if (now()->gt($this->exam->end_time->addMinutes(5))) {
            $isTimeout = true;
        }

        try {
            DB::transaction(function () {
                app(ExamService::class)->syncSessionScores($this->session);

                $this->session->update([
                    'token' => null,
                    'system_id' => null,
                    'status' => ExamSessionStatus::COMPLETED,
                    'finished_at' => now(),
                ]);

            });

            if ($isTimeout) {
                Notification::make()
                    ->title('Waktu Ujian Habis')
                    ->body('Sesi Anda telah berakhir. Jawaban yang tersimpan telah dikirim otomatis.')
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ujian Berhasil Dikirim')
                    ->body('Terima kasih, jawaban ujian Anda telah kami terima.')
                    ->success()
                    ->send();
            }

            return redirect()->to('/');

        } catch (Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Terjadi kesalahan teknis saat menyimpan: ' . $e->getMessage())
                ->warning()
                ->send();
        }

    }
}
