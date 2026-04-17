<?php

namespace App\Filament\Pages;

use App\Exports\QuestionPgTemplateExport;
use App\Exports\QuestionPgWordTemplateExport;
use App\Imports\QuestionPgExcelImport;
use App\Imports\QuestionPgWordImport;
use App\Models\QuestionCategory;
use App\Models\Subject;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class ImportQuestion extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Import Soal';
    protected static ?string $title = 'Import Soal';
    protected static ?string $navigationGroup = 'Manajemen Soal';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.import-question';
    public array $failures = [];
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Konfigurasi Import Soal')
                    ->description('Lengkapi data berikut dan pilih file template yang sesuai.')
                    ->schema([
                        Group::make([
                            Select::make('question_category_id')
                                ->label('Topik / Kategori')
                                ->options(QuestionCategory::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('subject_id')
                                ->label('Mata Pelajaran')
                                ->options(Subject::pluck('name', 'id'))
                                ->reactive()
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('type')
                                ->label('Tipe Soal dalam File')
                                ->searchable()
                                ->preload()
                                ->options([
                                    'pg' => 'Pilihan Ganda (Single & Multiple)',
                                    'tf' => 'True / False',
                                    'short' => 'Jawaban Singkat',
                                    'essay' => 'Essay',
                                ])
                                ->required(),
                        ])->columnSpan(1),

                        Group::make([
                            FileUpload::make('file')
                                ->label('File Template (Excel, Word, atau ZIP)')
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/zip',
                                    'application/x-zip-compressed',
                                    'multipart/x-zip',
                                ])
                                ->disk('local')
                                ->directory('temp-imports')
                                ->required()
                                ->preserveFilenames()
                                ->maxSize(20 * 1024 * 1024)
                                ->rules(['extensions:xlsx,docx,zip'])
                                ->validationMessages([
                                    'accepted_file_types' => 'Format file harus berupa .xlsx, .docx, atau .zip.',
                                    'extensions' => 'Format file harus berupa .xlsx, .docx, atau .zip.',
                                    'max' => 'Ukuran file terlalu besar. Maksimal 10MB.',
                                ])
                                ->extraAttributes(['class' => 'h-full'])
                                ->helperText(new HtmlString('
    <div class="text-xs space-y-2">
        <p>Ketentuan Unggah:</p>
        <ul class="list-disc ml-4 space-y-1">
            <li>Mendukung file <b>.xlsx</b>, <b>.docx</b>, atau <b>.zip</b>.</li>
            <li>Ukuran maksimal file upload adalah <b>20 MB</b>.</li>
        </ul>
    </div>
')),
                        ])->columnSpan(2),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->modalWidth('md')
                ->modalHeading('Pilih Tipe Template')
                ->modalDescription('Silakan pilih tipe soal untuk mengunduh format Excel yang sesuai.')
                ->modalSubmitActionLabel('Unduh Sekarang')
                ->modalCancelActionLabel('Kembali')
                ->form([
                    Select::make('format')
                        ->label('Format File')
                        ->options([
                            'excel' => 'Microsoft Excel (.xlsx)',
                            'word' => 'Microsoft Word (.docx)',
                        ])->default('excel')->required(),
                    Select::make('template_type')
                        ->label('Pilih Tipe Template')
                        ->options([
                            'pg' => 'Pilihan Ganda (Single & Multiple)',
                            'tf' => 'True / False',
                            'short' => 'Jawaban Singkat',
                            'essay' => 'Essay',
                        ])->required()
                ])
                ->action(function (array $data) {
                    if ($data['format'] === 'word') {
                        return match ($data['template_type']) {
                            'pg' => QuestionPgWordTemplateExport::export(),
                            default => Notification::make()->title('Template belum tersedia')->danger()->send(),
                        };
                    }

                    return match ($data['template_type']) {
                        'pg' => Excel::download(new QuestionPgTemplateExport, 'template_soal_pilihan_ganda_' . now()->format('Ymd_His') . '.xlsx'),
                        default => Notification::make()->title('Template belum tersedia')->danger()->send(),
                    };
                }),
        ];
    }

    public function submit()
    {
        $this->resetErrorBag();
        $state = $this->form->getState();
        $this->failures = [];
        $tempPath = null;

        DB::beginTransaction();
        try {
            $filePath = Storage::disk('local')->path($state['file']);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            $fileToProcess = $filePath;

            if ($extension === 'zip') {
                $tempPath = storage_path('app/temp-imports/' . uniqid());
                $zip = new ZipArchive;

                if ($zip->open($filePath) === TRUE) {
                    $zip->extractTo($tempPath);
                    $zip->close();
                } else {
                    throw new Exception("Gagal membuka file ZIP.");
                }

                $allFiles = File::allFiles($tempPath);

                $hasDocx = collect($allFiles)->first(fn($file) => $file->getFilename() === 'soal.docx');
                $hasXlsx = collect($allFiles)->first(fn($file) => $file->getFilename() === 'soal.xlsx');

                if ($hasDocx) {
                    $fileToProcess = $hasDocx->getRealPath();
                    $extension = 'docx';
                } elseif ($hasXlsx) {
                    $fileToProcess = $hasXlsx->getRealPath();
                    $extension = 'xlsx';
                } else {
                    throw new Exception("Di dalam ZIP wajib terdapat file dengan nama 'soal.docx' atau 'soal.xlsx'.");
                }
            }

            if ($state['type'] === 'pg') {
                if ($extension === 'docx') {
                    $import = new QuestionPgWordImport(
                        $state['subject_id'],
                        $state['question_category_id'],
                        $tempPath
                    );
                    $import->import($fileToProcess);
                } else {
                    $import = new QuestionPgExcelImport($state['subject_id'], $state['question_category_id'], $tempPath);
                    Excel::import($import, $fileToProcess);
                }
            }

            Notification::make()
                ->title('Proses Import Berhasil')
                ->success()
                ->send();

            $this->form->fill();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            // Ambil daftar kegagalan dari class import jika ada
            if (isset($import) && !empty($import->importErrors)) {
                $this->failures = $import->importErrors;

                Notification::make()
                    ->title('Import Gagal')
                    ->body('Terdapat beberapa kesalahan. Data tidak ada yang disimpan.')
                    ->danger()
                    ->send();
            } else {
                // Error sistem lainnya (file tidak ketemu, database mati, dll)
                Notification::make()
                    ->title('Terjadi Kesalahan Sistem')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        } finally {
            // AKAN SELALU JALAN: Baik berhasil maupun error
            if ($tempPath && File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }

            // Hapus juga file upload asli dari storage temp Laravel jika sudah tidak dibutuhkan
            if (isset($state['file'])) {
                Storage::disk('local')->delete($state['file']);
            }
        }
    }
}
