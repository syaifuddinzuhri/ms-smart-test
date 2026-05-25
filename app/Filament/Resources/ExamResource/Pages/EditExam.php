<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Enums\ExamStatus;
use App\Filament\Resources\ExamResource;
use App\Models\ExamClassroom;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['examClassrooms'] = $this->record->examClassrooms()
            ->get()
            ->map(function ($item) {
                return [
                    'classroom_id' => $item->classroom_id,
                    'min_total_score' => (int) $item->min_total_score,
                ];
            })
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            try {
                $examClassrooms = $data['examClassrooms'] ?? [];

                unset($data['examClassrooms']);

                $record->update($data);

                $record->examClassrooms()->delete();

                foreach ($examClassrooms as $item) {
                    ExamClassroom::create([
                        'exam_id' => $record->id,
                        'classroom_id' => $item['classroom_id'],
                        'min_total_score' => $item['min_total_score'],
                    ]);
                }

                return $record;

            } catch (Exception $e) {
                Notification::make()
                    ->title('Gagal Memperbarui Ujian')
                    ->body('Kesalahan: ' . $e->getMessage())
                    ->danger()
                    ->send();

                throw $e;
            }
        });
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Ujian Diperbarui')
            ->body('Perubahan data ujian dan target kelas berhasil disimpan.');
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect);
        } catch (ValidationException $e) {

            Notification::make()
                ->title('Validasi Gagal')
                ->body('Masih ada isian yang belum valid. Silakan periksa kembali form.')
                ->danger()
                ->send();

            throw $e; // penting
        }
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->getRecord()->is_lock) {
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

        if ($this->getRecord()->is_lock) {
            $breadcrumb[$lastStep] = 'Detail';
        } else {
            $breadcrumb[$lastStep] = 'Ubah';
        }

        return $breadcrumb;
    }

    protected function getFormActions(): array
    {
        return parent::getFormActions();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-m-arrow-left'),
            Action::make('help_scoring')
                ->label('Panduan Skema Poin')
                ->icon('heroicon-m-information-circle')
                ->color('info') // Warna biru
                ->modalHeading('Simulasi Perhitungan Poin & Pinalti')
                ->modalWidth('4xl')
                ->modalSubmitAction(false) // Hilangkan tombol submit modal
                ->modalCancelActionLabel('Tutup')
                ->modalContent(view('filament.pages.exam-scoring-help')), // Panggil file blade tadi
        ];
    }
}
