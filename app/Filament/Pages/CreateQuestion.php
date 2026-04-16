<?php

namespace App\Filament\Pages;

use App\Enums\QuestionType;
use App\Models\Question;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateQuestion extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Tambah Soal';
    protected static ?string $title = 'Tambah Soal';
    protected static ?string $navigationGroup = 'Manajemen Soal';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.create-question';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
        $this->data['options_count'] = 3;
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
                            ->label('Topik Materi')
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
                                if (in_array($state, ['single_choice', 'multiple_choice'])) {
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
                        RichEditor::make('question_text')
                            ->label("Isi Soal")
                            ->required()
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

                        TextInput::make('external_link')
                            ->label('Link Eksternal (YouTube, dll)')
                            ->url()
                            ->live(onBlur: true)
                            ->reactive()
                            ->placeholder('https://...')
                            ->columnSpanFull(),
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
                            ->visible(fn($get) => in_array($get('question_type'), ['single_choice', 'multiple_choice'])),

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
                                        if (!in_array($type, ['single_choice', 'true_false'])) {
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
                                'single_choice',
                                'multiple_choice',
                                'true_false'
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

    // ========================
    // GENERATE OPTIONS
    // ========================
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

    // ========================
    // CREATE
    // ========================
    public function create()
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
            if (in_array($type, ['single_choice', 'true_false'])) {

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
            if ($type === 'multiple_choice') {

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

            // ========================
            // CREATE QUESTION
            // ========================
            $question = Question::create([
                'id' => Str::uuid(),
                'subject_id' => $data['subject_id'],
                'question_category_id' => $data['question_category_id'],
                'question_type' => $data['question_type'],
                'question_text' => $data['question_text'],
                'correct_answer_text' => $data['correct_answer_text'] ?? null,
                'external_link' => $data['external_link'] ?? null,
            ]);

            // ========================
            // SAVE OPTIONS
            // ========================
            if (!empty($data['options'])) {
                foreach ($data['options'] as $index => $opt) {
                    $question->options()->create([
                        'id' => Str::uuid(),
                        'label' => $opt['label'],
                        'text' => $opt['text'],
                        'is_correct' => (bool) $opt['is_correct'],
                        'order' => $index,
                    ]);
                }
            }

            // ========================
            // SAVE ATTACHMENTS (QUESTION LEVEL)
            // ========================
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $index => $file) {
                    $newPath = generateFilePath(
                        'questions',
                        $question->id,
                        $index + 1,
                        $file
                    );

                    moveQuestionFile($file, $newPath);

                    $question->attachments()->create([
                        'id' => Str::uuid(),
                        'file_path' => $newPath,
                        'type' => detectFileType($newPath),
                    ]);
                }
            }

            DB::commit();

            Notification::make()
                ->title('Soal berhasil disimpan')
                ->success()
                ->send();

            $this->form->fill();

        } catch (\Throwable $e) {

            DB::rollBack();

            Notification::make()
                ->title('Gagal menyimpan soal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
