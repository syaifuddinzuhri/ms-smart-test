<?php

namespace App\Filament\Pages\Auth;

use App\Enums\PanelType;
use App\Enums\UserRole;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function getTitle(): string
    {
        return "Login";
    }

    public function getHeading(): string
    {
        return ''; // Kosongkan
    }

    public function getSubheading(): string
    {
        return ''; // Kosongkan jika ada
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)->send();
            return null;
        }

        $data = $this->form->getState();

        $user = User::where('username', $data['username'])->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            $this->throwFailureValidationException();
        }

        $panelId = filament()->getId();
        $role = $user->role->value;

        if ($role === UserRole::STUDENT->value) {
            $lifetime = config('session.lifetime') * 60;
            $threshold = time() - $lifetime;

            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('last_activity', '<', $threshold)
                ->delete();

            $activeSession = \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->first();

            if ($activeSession) {
                $isSameDevice = ($activeSession->ip_address === request()->ip() &&
                    $activeSession->user_agent === request()->userAgent());

                if ($isSameDevice) {
                    \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('Gagal Login')
                        ->body('Akun Anda sedang aktif di perangkat lain. Silakan hubungi Admin untuk reset sesi.')
                        ->danger()
                        ->send();

                    return null;
                }
            }
        }

        if ($panelId === PanelType::STUDENT->value && $role !== UserRole::STUDENT->value)
            $this->throwFailureValidationException();
        if ($panelId === PanelType::ADMIN->value && $role === UserRole::STUDENT->value)
            $this->throwFailureValidationException();

        session()->invalidate();
        session()->regenerate();

        \Filament\Facades\Filament::auth()->login($user, $data['remember'] ?? false);

        session()->save();

        return app(LoginResponse::class);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getUsernameFormComponent(), // Ganti Email ke Username
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Masuk')
            ->extraAttributes([
                'class' => 'w-full shadow-lg transform active:scale-95 transition-all duration-150',
            ]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
