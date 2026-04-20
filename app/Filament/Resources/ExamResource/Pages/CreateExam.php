<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use App\Models\ExamClassroom;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            try {
                $examClassrooms = $data['examClassrooms'] ?? [];

                unset($data['examClassrooms']);

                $record = static::getModel()::create($data);

                foreach ($examClassrooms as $item) {
                    ExamClassroom::create([
                        'exam_id' => $record->id,
                        'classroom_id' => $item['classroom_id'],
                        'min_total_score' => $item['min_total_score'],
                    ]);
                }

                return $record;

            } catch (\Exception $e) {
                Notification::make()
                    ->title('Gagal Menyimpan Ujian')
                    ->body('Terjadi kesalahan sistem: ' . $e->getMessage())
                    ->danger()
                    ->send();
                throw $e;
            }
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Ujian Berhasil Dibuat')
            ->body('Data ujian dan target kelas telah tersimpan.');
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $e) {

            Notification::make()
                ->title('Validasi Gagal')
                ->body('Masih ada isian yang belum valid. Silakan periksa kembali form.')
                ->danger()
                ->send();

            throw $e; // penting
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
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
