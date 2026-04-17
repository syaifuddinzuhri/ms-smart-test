<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Enums\QuestionType;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class EditQuestion extends Page
{
    protected static ?string $title = 'Edit Soal';

    protected static ?string $slug = 'edit-question/{record}';

    protected static string $view = 'filament.pages.edit-question';

    protected static bool $shouldRegisterNavigation = false;

    public ?Question $record = null;

    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = Question::with([
            'options' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'attachments'
        ])->find($record->id);

        $this->form->fill([
            'upload_token' => (string) Str::uuid(),
            'subject_id' => $this->record->subject_id,
            'question_category_id' => $this->record->question_category_id,
            'question_type' => $this->record->question_type->value,
            'question_text' => $this->record->question_text,
            'correct_answer_text' => $this->record->correct_answer_text,
            'external_link' => $this->record->external_link,
            'options_count' => count($this->record->options),
            'options' => $this->record->options->map(fn($opt) => [
                'label' => $opt->label,
                'text' => $opt->text,
                'is_correct' => $opt->is_correct,
            ])->toArray(),
            'attachments' => $this->record->attachments->pluck('file_path')->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->model(Question::class)
            ->schema([

                // ========================
                // KLASIFIKASI
                // ========================
                Section::make('Klasifikasi & Jenis')
                    ->schema([
                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Mata Pelajaran')
                            ->live(onBlur: true)
                            ->reactive()
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('question_category_id')
                            ->label('Topik')
                            ->relationship('questionCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->reactive()
                            ->required(),

                        Select::make('question_type')
                            ->label('Jenis Soal')
                            ->options(QuestionType::options())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {

                                // PG
                                if (in_array($state, [QuestionType::SINGLE_CHOICE->value, QuestionType::MULTIPLE_CHOICE->value])) {
                                    self::generateOptions(3, $set);
                                }

                                // TRUE FALSE
                                elseif ($state === 'true_false') {
                                    $set('options', [
                                        ['label' => 'A', 'text' => 'Benar', 'is_correct' => false],
                                        ['label' => 'B', 'text' => 'Salah', 'is_correct' => false],
                                    ]);
                                }

                                // lainnya
                                else {
                                    $set('options', []);
                                }
                            }),
                    ])
                    ->columnSpanFull(),

                // ========================
                // SOAL
                // ========================
                Section::make('Konten Pertanyaan')
                    ->schema([
                        Hidden::make('upload_token')
                            ->default(fn() => (string) Str::uuid()),

                        RichEditor::make('question_text')
                            ->label("Isi Soal")
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsVisibility('public')
                            ->fileAttachmentsDirectory(function (Get $get) {
                                return "questions/temp/" . $get('upload_token');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->columnSpanFull(),

                        FileUpload::make('attachments')
                            ->label('Lampiran (Gambar / Audio / Video / File)')
                            ->multiple()
                            ->disk('public')
                            ->directory('questions')
                            ->downloadable()
                            ->maxSize(10240)
                            ->maxFiles(3)
                            ->panelLayout('grid')
                            ->acceptedFileTypes([
                                'image/*',
                                'audio/*',
                                'video/*',
                                'application/pdf'
                            ])
                            ->previewable()
                            ->openable()
                            ->columnSpanFull()
                            ->extraInputAttributes([
                                'data-max-files' => 3,
                            ])
                            ->hint('Maksimal 3 file')
                            ->helperText(
                                fn($state) =>
                                count($state ?? []) >= 3
                                ? 'Maksimal 3 file (sudah penuh)'
                                : count($state ?? []) . ' / 3 file'
                            )
                            ->live(onBlur: true)
                            ->reactive(),

                        // TextInput::make('external_link')
                        //     ->label('Link Eksternal (YouTube, dll)')
                        //     ->url()
                        //     ->live(onBlur: true)
                        //     ->reactive()
                        //     ->placeholder('https://...')
                        //     ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                // ========================
                // JAWABAN
                // ========================
                Section::make('Jawaban')
                    ->schema([

                        // jumlah pilihan
                        Select::make('options_count')
                            ->label('Jumlah Pilihan')
                            ->options([
                                3 => '3',
                                4 => '4',
                                5 => '5',
                            ])
                            ->live()
                            ->afterStateUpdated(fn($state, $set) => self::generateOptions($state, $set))
                            ->visible(fn($get) => in_array($get('question_type'), [QuestionType::SINGLE_CHOICE->value, QuestionType::MULTIPLE_CHOICE->value])),

                        // OPTIONS (RELATIONAL)
                        Repeater::make('options')
                            ->label('Daftar Pilihan')
                            ->schema([
                                TextInput::make('label')
                                    ->readOnly()
                                    ->required()
                                    ->columnSpan(1),

                                RichEditor::make('text')
                                    ->required()
                                    ->columnSpan(8),

                                Toggle::make('is_correct')
                                    ->label('Kunci Jawaban')
                                    ->default(false)
                                    ->live()
                                    ->columnSpan(3)
                                    ->afterStateUpdated(function ($state, $set, $get, $component) {

                                        $type = $get('../../question_type');

                                        // hanya untuk single & true_false
                                        if (!in_array($type, [QuestionType::SINGLE_CHOICE->value, QuestionType::TRUE_FALSE->value])) {
                                            return;
                                        }

                                        if ($state) {
                                            // ambil path: data.options.0.is_correct
                                            $path = $component->getStatePath();

                                            // ambil index (0,1,2...)
                                            preg_match('/options\.(\d+)\.is_correct/', $path, $matches);
                                            $currentIndex = $matches[1] ?? null;

                                            if ($currentIndex === null)
                                                return;

                                            $options = $get('../../options');

                                            foreach ($options as $index => $opt) {
                                                $set("../../options.$index.is_correct", $index == $currentIndex);
                                            }
                                        }
                                    })
                            ])
                            ->columns(12)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn($get) => in_array($get('question_type'), [
                                QuestionType::SINGLE_CHOICE->value,
                                QuestionType::MULTIPLE_CHOICE->value,
                                QuestionType::TRUE_FALSE->value,
                            ])),

                        // SHORT ANSWER
                        TextInput::make('correct_answer_text')
                            ->label('Kunci Jawaban')
                            ->visible(fn($get) => $get('question_type') === 'short_answer'),

                    ])
                    ->visible(fn($get) => $get('question_type') !== 'essay' && $get('question_type')),

            ])
            ->columns(2)
            ->statePath('data');
    }

    public static function generateOptions($count, $set)
    {
        if (!$count)
            return;

        $labels = range('A', 'E');

        $options = [];

        for ($i = 0; $i < $count; $i++) {
            $options[] = [
                'label' => $labels[$i],
                'text' => '',
                'is_correct' => 0,
            ];
        }

        $set('options', $options);
    }

    public function save()
    {
        $this->resetErrorBag();

        $data = $this->form->getState();

        // ========================
        // VALIDASI
        // ========================
        $type = $data['question_type'];

        // ========================
        // VALIDASI KUNCI JAWABAN
        // ========================
        if ($type !== 'essay') {

            // SINGLE CHOICE & TRUE FALSE
            if (in_array($type, [QuestionType::SINGLE_CHOICE->value, QuestionType::TRUE_FALSE->value])) {

                $count = collect($data['options'] ?? [])
                    ->where('is_correct', 1)
                    ->count();

                if ($count !== 1) {
                    Notification::make()
                        ->title('Harus tepat 1 jawaban benar')
                        ->danger()
                        ->send();
                    return;
                }
            }

            // MULTIPLE CHOICE
            if ($type === QuestionType::MULTIPLE_CHOICE->value) {

                $count = collect($data['options'] ?? [])
                    ->where('is_correct', 1)
                    ->count();

                if ($count < 1) {
                    Notification::make()
                        ->title('Minimal 1 jawaban benar')
                        ->danger()
                        ->send();
                    return;
                }
            }

            // SHORT ANSWER
            if ($type === 'short_answer') {

                if (empty($data['correct_answer_text'])) {
                    Notification::make()
                        ->title('Kunci jawaban wajib diisi')
                        ->danger()
                        ->send();
                    return;
                }
            }
        }

        DB::beginTransaction();

        try {
            $newQuestionText = moveTempToPermanent(
                $data['upload_token'],
                'questions',
                $this->record->id,
                $data['question_text']
            );

            // ========================
            // CREATE QUESTION
            // ========================
            $this->record->update([
                'subject_id' => $data['subject_id'],
                'question_category_id' => $data['question_category_id'],
                'question_type' => $data['question_type'],
                'question_text' => $newQuestionText,
                'correct_answer_text' => $data['correct_answer_text'] ?? null,
                'external_link' => $data['external_link'] ?? null,
            ]);

            // ========================
            // SAVE OPTIONS
            // ========================
            $this->record->options()->delete();

            if (!empty($data['options'])) {
                $newOptions = moveTempToPermanent(
                    $data['upload_token'],
                    'questions',
                    $this->record->id,
                    $data['options']
                );

                $labels = range('A', 'E');
                foreach ($newOptions as $index => $opt) {
                    $this->record->options()->create([
                        'id' => Str::uuid(),
                        'label' => $labels[$index] ?? $opt['label'],
                        'text' => $opt['text'],
                        'is_correct' => (bool) $opt['is_correct'],
                        'order' => $index,
                    ]);
                }
            }

            // ========================
            // SAVE ATTACHMENTS (QUESTION LEVEL)
            // ========================
            // 3. Update Attachments
            if (isset($data['attachments']) && count($data['attachments']) > 0) {
                // Ambil daftar path file yang sekarang ada di form
                $currentFilePaths = $data['attachments'];

                // 1. Hapus record & file fisik yang sudah tidak ada di form (User menekan tombol hapus di UI)
                $deletedAttachments = $this->record->attachments()
                    ->whereNotIn('file_path', $currentFilePaths)
                    ->get();

                foreach ($deletedAttachments as $attachment) {
                    // Hapus file fisik dari storage jika perlu (opsional, tergantung helper moveQuestionFile Anda)
                    // Storage::disk('public')->delete($attachment->file_path);

                    $attachment->delete();
                }

                // 2. Tambahkan file yang benar-benar baru (biasanya berupa path temporary dari Livewire)
                foreach ($currentFilePaths as $index => $file) {
                    // Cek apakah file ini sudah ada di database (file lama)
                    $isExisting = $this->record->attachments()
                        ->where('file_path', $file)
                        ->exists();

                    if (!$isExisting) {
                        // Ini adalah file baru, jalankan logika pemindahan file Anda
                        $newPath = generateFilePath(
                            'questions',
                            $this->record->id,
                            $index + 1, // Urutan berdasarkan posisi di array
                            $file
                        );

                        moveQuestionFile($file, $newPath);

                        $this->record->attachments()->create([
                            'id' => Str::uuid(),
                            'file_path' => $newPath,
                            'type' => detectFileType($newPath),
                        ]);
                    }
                }
            } else {
                // Jika input attachments kosong sama sekali, hapus semua lampiran lama
                $this->record->attachments()->delete();
            }

            DB::commit();

            Notification::make()
                ->title('Soal berhasil diperbarui')
                ->success()
                ->send();

            return redirect()->to(QuestionList::getUrl());

        } catch (\Throwable $e) {

            DB::rollBack();

            Notification::make()
                ->title('Gagal memperbarui soal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn() => QuestionList::getUrl()),
        ];
    }
}
