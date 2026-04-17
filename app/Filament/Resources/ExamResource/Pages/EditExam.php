<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Enums\ExamStatus;
use App\Filament\Resources\ExamResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    public function getTitle(): string|Htmlable
    {
        if ($this->getRecord()->status === ExamStatus::CLOSED) {
            return 'Detail Ujian';
        }

        return 'Ubah Daftar Ujian';
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();
        $breadcrumb = parent::getBreadcrumbs();

        // Ambil kunci terakhir dari array breadcrumb (biasanya label halaman aktif)
        $lastStep = array_key_last($breadcrumb);

        if ($this->getRecord()->status === ExamStatus::CLOSED) {
            $breadcrumb[$lastStep] = 'Detail';
        } else {
            $breadcrumb[$lastStep] = 'Ubah';
        }

        return $breadcrumb;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-m-arrow-left'),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->getRecord()->status === ExamStatus::CLOSED) {
            return [];
        }

        return parent::getFormActions();
    }
}
