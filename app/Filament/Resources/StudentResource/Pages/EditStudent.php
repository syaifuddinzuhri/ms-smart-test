<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_name'] = $this->record->user->name;
        $data['user_username'] = $this->record->user->username;
        $data['user_email'] = $this->record->user->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $cleanUsername = normalizeUsername($data['user_username']);

        $userData = [
            'name' => $data['user_name'],
            'username' => $cleanUsername,
            'email' => $data['user_email'],
        ];

        if (isset($data['user_password']) && filled($data['user_password'])) {
            $userData['password'] = Hash::make($data['user_password']);
        }

        $this->record->user->update($userData);

        return Arr::except($data, ['user_name', 'user_username', 'user_email', 'user_password', 'user_role']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
