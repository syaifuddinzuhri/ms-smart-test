<?php

namespace App\Enums;

use ArchTech\Enums\Options;
use ArchTech\Enums\Values;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExamStatus: string implements HasLabel, HasColor
{
    use Options, Values;

    case PENDING = 'pending'; // Ujian belum dibuka jadwalnya
    case NOT_STARTED = 'not_started'; // Ujian sudah buka, tapi siswa belum mulai
    case ONGOING = 'ongoing'; // Siswa sedang mengerjakan
    case PAUSE = 'pause'; // Pause
    case COMPLETED = 'completed'; // Siswa sudah selesai

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Jadwal',
            self::NOT_STARTED => 'Belum Dikerjakan',
            self::ONGOING => 'Sedang Berlangsung',
            self::PAUSE => 'Terjeda',
            self::COMPLETED => 'Sudah Selesai',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::NOT_STARTED => 'danger',
            self::ONGOING => 'warning',
            self::PAUSE => 'info',
            self::COMPLETED => 'success',
        };
    }
}
