<?php

namespace App\Filament\Resources\ExamMonitoringResource\Pages;

use App\Filament\Resources\ExamMonitoringResource;
use App\Enums\ExamSessionStatus;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions;

class ListExamMonitorings extends ManageRecords
{
    protected static string $resource = ExamMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->color('warning')
                ->outlined(true)
                ->icon('heroicon-m-arrow-path')
                ->action(fn() => $this->dispatch('refreshTable')),
        ];
    }


    protected function getHeaderWidgets(): array
    {
        return [
            ExamMonitoringResource\Widgets\MonitoringOverview::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'stats' => $this->getCurrentStats(),
        ];
    }

    protected function handleTableFilterUpdates(): void
    {
        parent::handleTableFilterUpdates();
        $this->dispatch('updateStats', stats: $this->getCurrentStats());
    }

    public function updatedTableSearch(): void
    {
        $this->dispatch('updateStats', stats: $this->getCurrentStats());
    }

    protected function getCurrentStats(): ?array
    {
        $examId = $this->tableFilters['exam']['value'] ?? null;

        if (!filled($examId)) {
            return null;
        }

        $query = $this->getFilteredTableQuery();

        return [
            'total' => (clone $query)->count(),
            'ongoing' => (clone $query)->where('status', ExamSessionStatus::ONGOING)->count(),
            'completed' => (clone $query)->where('status', ExamSessionStatus::COMPLETED)->count(),
            'violation' => (clone $query)->where('violation_count', '>', 0)->count(),
        ];
    }
}
