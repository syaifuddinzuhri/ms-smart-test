<?php

namespace App\Models;

use App\Enums\PanelType;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'role' => UserRole::class,
        'is_active' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        $role = $this->role->value;

        if ($panel->getId() === PanelType::ADMIN->value) {
            return $role !== UserRole::STUDENT->value;
        }

        if ($panel->getId() === PanelType::STUDENT->value) {
            return $role === UserRole::STUDENT->value;
        }

        return false;
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }
}
