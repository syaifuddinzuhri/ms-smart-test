<?php

namespace App\Enums;

use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum UserRole: string
{
    use Options, Values;

    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case SUPERVISOR = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::TEACHER => 'Guru',
            self::STUDENT => 'Siswa',
            self::SUPERVISOR => 'Pengawas',
        };
    }
}
