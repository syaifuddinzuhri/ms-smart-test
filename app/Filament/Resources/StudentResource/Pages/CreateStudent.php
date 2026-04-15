<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\StudentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $cleanUsername = normalizeUsername($data['user_username']);

        $user = User::create([
            'name' => $data['user_name'],
            'username' => $cleanUsername,
            'email' => $data['user_email'],
            'password' => Hash::make($data['user_password']),
            'role' => UserRole::STUDENT->value,
        ]);

        $data['user_id'] = $user->id;

        return Arr::except($data, ['user_name', 'user_username', 'user_email', 'user_password', 'user_role']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
