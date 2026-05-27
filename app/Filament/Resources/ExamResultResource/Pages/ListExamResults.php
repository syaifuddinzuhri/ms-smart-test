<?php

namespace App\Filament\Resources\ExamResultResource\Pages;

use App\Enums\ExamSessionStatus;
use App\Filament\Resources\ExamResultResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExamResults extends ListRecords
{
    protected static string $resource = ExamResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('finalizeAll')
                ->label('Finalisasi Hasil Ujian')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalisasi Semua Hasil Ujian?')
                ->modalDescription(function () {
                    $count = $this->getFilteredTableQuery()
                        ->where('status', ExamSessionStatus::COMPLETED)
                        ->whereNull('finalized_at')
                        ->count();

                    return "Sebanyak {$count} data yang belum difinalisasi akan dikunci. Nilai tidak dapat diubah lagi setelah proses ini.";
                })
                ->modalSubmitActionLabel('Ya, Finalisasi Sekarang')
                ->visible(fn() => filled($this->tableFilters['exam_id']['value'] ?? null))
                ->action(function () {
                    $count = $this->getFilteredTableQuery()
                        ->where('status', ExamSessionStatus::COMPLETED)
                        ->whereNull('finalized_at')
                        ->update(['finalized_at' => now()]);

                    Notification::make()
                        ->title('Finalisasi Berhasil')
                        ->body("{$count} data berhasil difinalisasi.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
