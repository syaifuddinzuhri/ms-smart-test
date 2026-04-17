<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Enums\UserRole;
use App\Exports\StudentTemplateExport;
use Filament\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class ImportStudent extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Import Peserta';
    protected static ?string $title = 'Import Peserta';
    protected static ?string $navigationGroup = 'Manajemen Peserta';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.import-student';

    public ?array $data = [];

    public ?array $importData = [];

    public int $currentPage = 1;
    public int $perPage = 10;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file_import')
                    ->label('Pilih File Excel (.xlsx)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->storeFiles(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $livewire) {
                        if (empty($state)) {
                            $livewire->resetImport();
                        }
                    })
            ])
            ->statePath('data');
    }

    // Fungsi Preview
    public function previewImport()
    {
        $formData = $this->form->getState();
        $file = $formData['file_import'];

        try {
            $rows = Excel::toArray([], $file)[0];
            $cleanData = array_slice($rows, 8);

            $processedData = [];
            $tempUsernames = []; // Untuk deteksi duplikat sesama isi Excel
            $tempNisns = [];

            foreach ($cleanData as $row) {
                if (empty($row[0]))
                    continue;

                $errors = [];
                $username = normalizeUsername($row[1] ?? '');
                $nisn = $row[3] ?? '';
                $classCode = $row[4] ?? '';

                // 1. Validasi Username (DB & Internal Excel)
                if (empty($username)) {
                    $errors[] = "Username kosong";
                } elseif (User::where('username', $username)->exists()) {
                    $errors[] = "Username sudah terdaftar di sistem";
                } elseif (in_array($username, $tempUsernames)) {
                    $errors[] = "Username ganda dalam file Excel";
                }
                $tempUsernames[] = $username;

                // 2. Validasi NISN (DB & Internal Excel)
                if (empty($nisn)) {
                    $errors[] = "NISN kosong";
                } elseif (Student::where('nisn', $nisn)->exists()) {
                    $errors[] = "NISN sudah terdaftar di sistem";
                } elseif (in_array($nisn, $tempNisns)) {
                    $errors[] = "NISN ganda dalam file Excel";
                }
                $tempNisns[] = $nisn;

                // 3. Validasi Kelas
                if (empty($classCode)) {
                    $errors[] = "Kode kelas kosong";
                } elseif (!Classroom::where('code', $classCode)->exists()) {
                    $errors[] = "Kode kelas tidak ditemukan";
                }

                // Simpan data dalam bentuk asosiatif agar lebih mudah dibaca di Blade
                $processedData[] = [
                    'name' => $row[0],
                    'username' => $username,
                    'email' => $row[2],
                    'nisn' => $nisn,
                    'class_code' => $classCode,
                    'pob' => $row[5],
                    'dob' => $row[6],
                    'errors' => $errors, // Simpan array error di sini
                ];
            }

            if (count($processedData) > 500) {
                Notification::make()->title('Terlalu Banyak Data (Max 500)')->danger()->send();
                return;
            }

            $this->importData = $processedData;
            $this->currentPage = 1;

        } catch (\Exception $e) {
            Notification::make()->title('File tidak valid')->danger()->send();
        }
    }

    public function getPaginatedData(): array
    {
        return array_slice(
            $this->importData,
            ($this->currentPage - 1) * $this->perPage,
            $this->perPage
        );
    }

    public function nextPage()
    {
        if ($this->currentPage < $this->getTotalPages())
            $this->currentPage++;
    }
    public function previousPage()
    {
        if ($this->currentPage > 1)
            $this->currentPage--;
    }
    public function getTotalPages()
    {
        return ceil(count($this->importData) / $this->perPage);
    }

    public function resetImport()
    {
        $this->importData = [];

        $this->data = [];
        $this->form->fill();

        $this->currentPage = 1;
    }

    public function saveImport()
    {
        if (empty($this->importData))
            return;

        $successCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($this->importData as $item) {
                if (!empty($item['errors'])) {
                    $skippedCount++;
                    continue;
                }

                $classroom = Classroom::where('code', $item['class_code'])->first();

                $user = User::create([
                    'name' => $item['name'],
                    'username' => $item['username'],
                    'email' => $item['email'] ?? $item['username'] . '@mail.com',
                    'password' => Hash::make($item['nisn']),
                    'role' => UserRole::STUDENT->value,
                ]);

                // 5. Buat Data Siswa
                Student::create([
                    'user_id' => $user->id,
                    'classroom_id' => $classroom->id,
                    'nisn' => $item['nisn'],
                    'pob' => $item['pob'],
                    'dob' => $item['dob'],
                ]);

                $successCount++;
            }

            DB::commit();

            $message = new HtmlString("
            <div class='text-sm'>
                <p><b>Berhasil:</b> <span class='text-green-600 font-bold'>{$successCount}</span> data</p>
                <p><b>Gagal:</b> <span class='text-danger-600 font-bold'>{$skippedCount}</span> data</p>
            </div>
        ");

            $statusNotification = Notification::make()
                ->title('Proses Import Selesai')
                ->body($message)
                ->success();

            // Jika ada yang gagal, beri warna peringatan (amber) bukan sukses murni
            if ($skippedCount > 0 && $successCount > 0) {
                $statusNotification->warning();
            } elseif ($successCount === 0) {
                $statusNotification->danger()->title('Import Gagal Total');
            }

            $statusNotification->send();

            if ($successCount > 0) {
                return redirect()->to(\App\Filament\Resources\StudentResource::getUrl('index'));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Terjadi Kesalahan Sistem')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new StudentTemplateExport, 'template_peserta_' . now()->format('Ymd_His') . '.xlsx');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->color('gray')
                ->action('downloadTemplate'),
        ];
    }
}
