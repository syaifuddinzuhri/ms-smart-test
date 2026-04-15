<?php

namespace App\Filament\Student\Pages;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;

class StartTest extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Simulasi Ujian Online';

    public $exam_id;

    // State Navigasi
    public $activeTab = 'pg'; // 'pg' atau 'essay'
    public $currentStep = 1;
    public $totalPG = 3; // Contoh ada 3 soal PG
    public $totalEssay = 2; // Contoh ada 2 soal Essay

    public ?array $data = [];

    public $doubtfulQuestions = [];
    public $durationInSeconds = 3600;

    protected static string $view = 'filament.student.pages.start-test';

    public function mount(): void
    {
        $this->exam_id = request()->query('exam_id');

        // Logika mengambil durasi dari database (Contoh)
        // $exam = Exam::find($this->exam_id);
        // $this->durationInSeconds = $exam->duration * 60;

        $this->form->fill();
    }

    public bool $isLocked = false;

    // Method untuk mengunci ujian
    public function lockExam(): void
    {
        // if (config('app.env') === 'local')
        //     return;

        $this->isLocked = true;

        // Nanti di sini tambahkan logic database:
        // ExamSession::where('user_id', auth()->id())->update(['is_locked' => true]);
    }

    public function timeOut(): void
    {
        $this->submit();
        Notification::make()
            ->title('Waktu Habis')
            ->body('Ujian otomatis tersimpan karena waktu telah selesai.')
            ->warning()
            ->send();
    }

    public function backToDashboard(): void
    {
        redirect()->to('/student/input-token?exam_id=' . $this->exam_id);
    }

    public function toggleDoubt($key)
    {
        if (in_array($key, $this->doubtfulQuestions)) {
            $this->doubtfulQuestions = array_diff($this->doubtfulQuestions, [$key]);
        } else {
            $this->doubtfulQuestions[] = $key;
        }
    }

    public function goToStep($tab, $step)
    {
        $this->activeTab = $tab;
        $this->currentStep = $step;
    }

    // Fungsi pembantu untuk cek status di Blade
    public function getQuestionStatus($key)
    {
        $isAnswered = !empty($this->data[$key]);
        $isDoubtful = in_array($key, $this->doubtfulQuestions);

        if ($isDoubtful)
            return 'doubtful';
        if ($isAnswered)
            return 'answered';
        return 'unanswered';
    }

    public function submitAction(): Action
    {
        return Action::make('submit')
            ->label('Submit & Kirim Ujian')
            ->icon('heroicon-m-paper-airplane')
            ->color('info')
            ->size('md')
            ->requiresConfirmation()
            ->modalHeading('Kirim Jawaban Ujian?')
            ->modalDescription('Pastikan semua jawaban sudah benar. Setelah dikirim, Anda tidak dapat mengubah jawaban lagi.')
            ->modalSubmitActionLabel('Ya, Kirim Sekarang')
            ->modalCancelActionLabel('Batal')
            ->modalIcon('heroicon-o-check-circle')
            ->modalAlignment(Alignment::Center)
            ->action(fn() => $this->submit());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // KELOMPOK SOAL PILIHAN GANDA
                \Filament\Forms\Components\Group::make([
                    Radio::make('q1')
                        ->label('1. Apa kepanjangan dari CBT?')
                        ->options(['a' => 'Computer Based Test', 'b' => 'Common Basic Task'])
                        ->live()
                        ->visible(fn() => $this->activeTab === 'pg' && $this->currentStep === 1),

                    Radio::make('q2')
                        ->label('2. Laravel menggunakan bahasa pemrograman apa?')
                        ->options(['a' => 'PHP', 'b' => 'Javascript', 'c' => 'Ruby'])
                        ->live()
                        ->visible(fn() => $this->activeTab === 'pg' && $this->currentStep === 2),

                    CheckboxList::make('q3')
                        ->label('3. Pilih framework CSS (Bisa lebih dari satu)')
                        ->options(['a' => 'Tailwind', 'b' => 'Bootstrap', 'c' => 'Laravel'])
                        ->live()
                        ->visible(fn() => $this->activeTab === 'pg' && $this->currentStep === 3),
                ]),

                // KELOMPOK SOAL ESSAY
                \Filament\Forms\Components\Group::make([
                    TextInput::make('q4')
                        ->label('1. Siapa penemu World Wide Web?')
                        ->live()
                        ->visible(fn() => $this->activeTab === 'essay' && $this->currentStep === 1),

                    RichEditor::make('q5')
                        ->label('2. Jelaskan perbedaan Frontend dan Backend!')
                        ->live()
                        ->visible(fn() => $this->activeTab === 'essay' && $this->currentStep === 2),
                ]),
            ])
            ->statePath('data');
    }

    // Navigasi Next
    public function next()
    {
        if ($this->activeTab === 'pg') {
            if ($this->currentStep < $this->totalPG) {
                $this->currentStep++;
            } else {
                // Jika PG sudah habis, pindah ke Essay
                $this->activeTab = 'essay';
                $this->currentStep = 1;
            }
        } else {
            if ($this->currentStep < $this->totalEssay) {
                $this->currentStep++;
            }
        }
    }

    // Navigasi Prev
    public function previous()
    {
        if ($this->activeTab === 'essay') {
            if ($this->currentStep > 1) {
                $this->currentStep--;
            } else {
                // Kembali ke PG soal terakhir
                $this->activeTab = 'pg';
                $this->currentStep = $this->totalPG;
            }
        } else {
            if ($this->currentStep > 1) {
                $this->currentStep--;
            }
        }
    }

    // Ganti Tab Manual
    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->currentStep = 1;
    }

    public function isAllAnswered(): bool
    {
        $totalSoal = $this->totalPG + $this->totalEssay;

        // Filter data: hapus yang null, string kosong, atau array kosong
        $answeredData = collect($this->data)->filter(function ($value) {
            if (is_array($value)) {
                return count($value) > 0;
            }
            return !empty($value);
        });

        return $answeredData->count() >= $totalSoal;
    }

    public function submit()
    {
        $jawaban = $this->form->getState();

        // Simulasi notifikasi sukses
        Notification::make()
            ->title('Jawaban Berhasil Terkirim')
            ->body('Terima kasih, jawaban ujian Anda telah kami terima.')
            ->success()
            ->send();

        // Redirect kembali ke daftar ujian setelah 2 detik (opsional)
        return redirect()->to('/student');
    }
}
