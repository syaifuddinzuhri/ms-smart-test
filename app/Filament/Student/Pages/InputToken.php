<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Enums\ExamTokenType;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\User;
use Exception;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InputToken extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = '';
    protected static string $view = 'filament.student.pages.input-token';

    public ?Exam $record = null;

    public $exam_id;
    public ?array $data = [];

    public function mount()
    {
        $this->exam_id = request()->query('exam_id');

        if (!$this->exam_id) {
            return redirect()->to('/student');
        }

        $this->record = Exam::where('status', ExamStatus::ACTIVE)->find($this->exam_id);

        if (!$this->record) {
            return redirect()->to('/student');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('token')
                            ->label('Masukkan Token Ujian')
                            ->placeholder('******')
                            ->required()
                            ->maxLength(10)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase; text-align: center; font-size: 1.5rem; font-weight: 800; letter-spacing: 0.1em;'])
                            ->autofocus(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('validateToken')
                ->label('Verifikasi & Mulai Ujian')
                ->submit('form')
                ->color('primary')
                ->icon('heroicon-m-check-badge')
                ->size('lg'),
        ];
    }

    public function validateToken()
    {
        $inputData = $this->form->getState();
        $tokenInput = strtoupper($inputData['token']);

        $user = Auth::user();

        $session = ExamSession::where('exam_id', $this->exam_id)
            ->where('user_id', $user->id)
            ->first();

        $requiredType = $session ? ExamTokenType::RELOGIN : ExamTokenType::ACCESS;

        $validToken = ExamToken::where('exam_id', $this->exam_id)
            ->where('token', $tokenInput)
            ->where('type', $requiredType)
            ->where('is_active', true)
            ->where('expired_at', '>', now())
            ->first();

        if (!$validToken) {
            Notification::make()
                ->title('Token Tidak Valid')
                ->body($session
                    ? 'Token kadaluarsa / Sesi Anda terdeteksi sudah berjalan. Silahkan masukkan token akses masuk ulang dari pengawas.'
                    : 'Token akses salah, kadaluarsa, atau tidak sesuai tipe.')
                ->danger()
                ->send();
            return;
        }

        $maxAllowed = ($requiredType === ExamTokenType::ACCESS)
            ? config('exam_token.max_usage', 50)
            : 1;

        if ($validToken->used_count >= $maxAllowed) {
            $validToken->update(['is_active' => false]);
            Notification::make()
                ->title('Kuota penggunaan token akses ini sudah habis.')
                ->danger()
                ->send();
            return;
        }

        $tokenSession = null;

        try {
            $tokenSession = DB::transaction(function () use ($validToken, $maxAllowed, $user) {
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

                $token = $this->initializeExamSession($user);
                return $token;
            });
        } catch (Exception $e) {
            Notification::make()
                ->title('Gagal Memulai Ujian')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        if (!$tokenSession) {
            Notification::make()
                ->title('Gagal Memulai Ujian')
                ->danger()
                ->send();
        }

        Notification::make()
            ->title('Verifikasi Berhasil')
            ->success()
            ->send();

        return redirect()->to(route('filament.student.pages.start-test', ['token' => $tokenSession]));
    }

    private function initializeExamSession(User $user)
    {
        $userId = $user->id;
        $examId = $this->record->id;

        $session = ExamSession::firstOrNew(
            ['exam_id' => $examId, 'user_id' => $userId]
        );

        if ($session->exists && $session->status === ExamSessionStatus::COMPLETED) {
            throw new Exception("Anda sudah menyelesaikan ujian ini.");
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $updateData = [
            'token' => $tokenHash,
            'system_id' => generate_exam_system_id($tokenHash),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => getDeviceInfo(),
            'status' => ExamSessionStatus::ONGOING->value,
        ];

        if (!$session->started_at) {
            $uniqueQuestionSeed = crc32($userId . $examId . 'question' . now()->timestamp);
            $uniqueOptionSeed = crc32($userId . $examId . 'option' . now()->timestamp);

            $updateData = array_merge($updateData, [
                'started_at' => now(),
                'remaining_duration' => $this->record->duration * 60,
                'question_seed' => $uniqueQuestionSeed,
                'option_seed' => $uniqueOptionSeed,
            ]);
        }

        $session->fill($updateData);
        $session->save();

        return $plainToken;
    }
}
