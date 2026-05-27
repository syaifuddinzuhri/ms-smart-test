<?php

namespace App\Filament\Resources\ExamMonitoringResource\Widgets;

use Filament\Widgets\Widget;

class MonitoringOverview extends Widget
{
    protected static string $view = 'filament.widgets.monitoring-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Properti ini akan diisi secara otomatis oleh Filament
     * dari return value getWidgetData() yang ada di Page.
     */
    public ?array $stats = null;

    protected static bool $isLazy = false;

    /**
     * Mendengarkan event dari Page.
     * Saat event 'updateStats' diterima, panggil fungsi 'handleUpdateStats'
     */
    protected $listeners = [
        'updateStats' => 'handleUpdateStats',
        'refreshTable' => '$refresh',
    ];

    /**
     * Fungsi untuk menerima kiriman data dari Page
     */
    public function handleUpdateStats(?array $stats): void
    {
        $this->stats = $stats;
    }
}
