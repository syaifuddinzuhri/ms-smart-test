<?php

namespace App\Filament\Pages;

use App\Enums\QuestionGroupType;
use App\Exports\QuestionChoiceExcelTemplateExport;
use App\Exports\QuestionChoiceWordTemplateExport;
use App\Exports\QuestionTrueFalseExcelTemplateExport;
use App\Exports\QuestionTrueFalseWordTemplateExport;
use App\Imports\QuestionChoiceExcelImport;
use App\Imports\QuestionChoiceWordImport;
use App\Imports\QuestionTrueFalseExcelImport;
use App\Imports\QuestionTrueFalseWordImport;
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
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelFactory;
use PhpOffice\PhpWord\IOFactory as WordFactory;

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
                                ->options(QuestionGroupType::options())
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
                                    'max' => 'Ukuran file terlalu besar. Maksimal 20MB.',
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
                        ])->default('excel')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('template_type')
                        ->label('Pilih Tipe Template')
                        ->searchable()
                        ->preload()
                        ->options(QuestionGroupType::options())
                        ->required()
                ])
                ->action(function (array $data) {
                    if ($data['format'] === 'word') {
                        return match ($data['template_type']) {
                            'pg' => QuestionChoiceWordTemplateExport::export(),
                            'tf' => QuestionTrueFalseWordTemplateExport::export(),
                            default => Notification::make()->title('Template belum tersedia')->danger()->send(),
                        };
                    }

                    return match ($data['template_type']) {
                        'pg' => Excel::download(new QuestionChoiceExcelTemplateExport, 'template_soal_pilihan_ganda_' . now()->format('Ymd_His') . '.xlsx'),
                        'tf' => Excel::download(new QuestionTrueFalseExcelTemplateExport, 'template_soal_benar_salah_' . now()->format('Ymd_His') . '.xlsx'),
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

            $type = QuestionGroupType::from($state['type']);
            $format = ($extension === 'docx') ? 'docx' : 'excel';

            try {
                $this->validateTemplate($fileToProcess, $format, $type);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }

            $importerMap = [
                'pg' => [
                    'docx' => QuestionChoiceWordImport::class,
                    'excel' => QuestionChoiceExcelImport::class,
                ],
                'tf' => [
                    'docx' => QuestionTrueFalseWordImport::class,
                    'excel' => QuestionTrueFalseExcelImport::class,
                ],
            ];

            $importClass = $importerMap[$type->value][$format];

            if (!isset($importClass)) {
                throw new Exception("Import untuk tipe {$type->getLabel()} dengan format {$extension} belum didukung.");
            }

            if ($format === 'docx') {
                $import = new $importClass(
                    $state['subject_id'],
                    $state['question_category_id'],
                    $tempPath
                );
                $import->import($fileToProcess);
            } else {
                $import = new $importClass(
                    $state['subject_id'],
                    $state['question_category_id'],
                    $tempPath
                );
                Excel::import($import, $fileToProcess);
            }

            Notification::make()
                ->title('Proses Import Berhasil')
                ->success()
                ->send();

            $this->form->fill();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            if (isset($import) && !empty($import->importErrors)) {
                $this->failures = $import->importErrors;

                Notification::make()
                    ->title('Import Gagal')
                    ->body('Terdapat beberapa kesalahan. Data tidak ada yang disimpan.')
                    ->danger()
                    ->send();
            } else {
                Notification::make()
                    ->title('Terjadi Kesalahan Sistem')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        } finally {
            if ($tempPath && File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }

            if (isset($state['file'])) {
                Storage::disk('local')->delete($state['file']);
            }
        }
    }

    private function validateTemplate($filePath, $format, $type)
    {
        $expectedKeyword = $type->getTemplateKeyword();
        $actualText = '';

        if (!file_exists($filePath)) {
            throw new Exception("File tidak ditemukan di sistem atau sesi upload telah berakhir. Silakan refresh halaman dan upload ulang file Anda.");
        }

        try {
            if ($format === 'excel') {
                $spreadsheet = ExcelFactory::load($filePath);
                $actualText = $spreadsheet->getActiveSheet()->getCell('A1')->getValue();
            } else {
                $phpWord = WordFactory::load($filePath);
                $sections = $phpWord->getSections();
                if (isset($sections[0])) {
                    $elements = $sections[0]->getElements();
                    if (isset($elements[0]) && method_exists($elements[0], 'getText')) {
                        $actualText = $elements[0]->getText();
                    }
                }
            }
        } catch (\Throwable $th) {
            throw new Exception("Gagal membaca struktur file tersebut. File mungkin rusak atau tidak terbaca. Silakan refresh halaman dan upload ulang file Anda.");
        }

        if (!str_contains(strtoupper($actualText), strtoupper($expectedKeyword))) {
            throw new Exception(
                "File yang diunggah bukan template {$type->getLabel()}. " .
                "Harap gunakan template yang sesuai agar data terbaca dengan benar."
            );
        }
    }
}
