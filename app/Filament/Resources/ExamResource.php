<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Ujian';

    protected static ?string $modelLabel = 'Daftar Ujian';
    protected static ?string $pluralModelLabel = 'Daftar Ujian';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Setup Ujian')
                    ->tabs([
                        Tab::make('Informasi Dasar')
                            ->schema([
                                Select::make('exam_category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->live()
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - {$record->academicYear?->name}")
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTitle($set, $get)),
                                Select::make('subject_id')
                                    ->label('Mata Pelajaran')
                                    ->relationship('subject', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTitle($set, $get)),
                                TextInput::make('title')
                                    ->label('Judul Ujian')
                                    ->required()
                                    ->placeholder('Contoh: UTS Matematika Gasal'),
                                TextInput::make('duration')
                                    ->label('Durasi (Menit)')
                                    ->numeric()
                                    ->suffix('Menit')
                                    ->required(),
                                DateTimePicker::make('start_time')
                                    ->label('Waktu Mulai')
                                    ->required()
                                    ->native(false) // Menggunakan picker Filament yang lebih rapi
                                    ->displayFormat('d/m/Y H:i') // Format yang tampil di UI
                                    ->format('Y-m-d H:i:00') // Format yang disimpan ke Database (detik dipaksa 00)
                                    ->seconds(false), // Menghilangkan inputan detik di picker

                                DateTimePicker::make('end_time')
                                    ->label('Waktu Selesai')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->format('Y-m-d H:i:00')
                                    ->seconds(false),

                                Select::make('classrooms') // Nama field harus sesuai nama relasi di Model Exam
                                    ->label('Target Kelas')
                                    ->relationship('classrooms', 'name') // Mengambil dari relasi classrooms, kolom name
                                    ->multiple() // Mengaktifkan pilihan ganda
                                    ->preload() // Memuat data di awal agar user mudah mencari
                                    ->searchable()
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - {$record->major?->name}")
                                    ->placeholder('Pilih satu atau lebih kelas...')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Tab::make('Sistem Poin')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Pilihan Ganda')
                                            ->schema([
                                                TextInput::make('point_pg')->label('Poin Benar')->numeric()->default(1),
                                                TextInput::make('point_pg_wrong')->label('Poin Salah')->numeric()->default(0),
                                                TextInput::make('point_pg_null')->label('Poin Kosong')->numeric()->default(0),
                                            ])->columnSpan(1),
                                        Section::make('Jawaban Singkat')
                                            ->schema([
                                                TextInput::make('point_short_answer')->label('Poin Benar')->numeric()->default(1),
                                                TextInput::make('point_short_answer_wrong')->label('Poin Salah')->numeric()->default(0),
                                                TextInput::make('point_short_answer_null')->label('Poin Kosong')->numeric()->default(0),
                                            ])->columnSpan(1),
                                        Section::make('Essay')
                                            ->schema([
                                                TextInput::make('point_essay_max')->label('Poin Maksimal')->numeric()->default(10),
                                                TextInput::make('point_essay_null')->label('Poin Kosong')->numeric()->default(0),
                                            ])->columnSpan(1),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    protected static function updateTitle(Set $set, Get $get): void
    {
        $categoryId = $get('exam_category_id');
        $subjectId = $get('subject_id');

        if ($categoryId && $subjectId) {
            $category = ExamCategory::with(['academicYear'])->find($categoryId);
            $subjectName = Subject::find($subjectId)?->name;

            if ($category && $subjectName) {
                $set('title', "{$category?->name} - {$subjectName} - {$category?->academicYear?->name}");
            }
        } else {
            $set('title', "");
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Ujian')
                    ->searchable()
                    // Menampilkan Kelas & Jurusan sebagai sub-teks di bawah judul agar hemat ruang
                    ->description(function (Exam $record): string {
                        $classrooms = $record->classrooms->map(fn($c) => "{$c->name} ({$c->major?->name})");

                        if ($classrooms->count() <= 3) {
                            return $classrooms->implode(', ');
                        }

                        // Ambil 3 pertama, gabungkan, lalu tambahkan keterangan sisa
                        $firstThree = $classrooms->take(3)->implode(', ');
                        $remainingCount = $classrooms->count() - 3;

                        return "{$firstThree} ... (+{$remainingCount} lainnya)";
                    }),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Waktu')
                    ->dateTime('d F Y H:i')
                    // Menampilkan End Time di bawah Start Time
                    ->description(fn(Exam $record): string => 'Selesai: ' . $record->end_time?->format('d F Y H:i')),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Durasi')
                    ->numeric()
                    ->suffix(' Menit')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Exam $record): string {
                        $now = now();
                        if ($now < $record->start_time)
                            return 'Belum Mulai';
                        if ($now > $record->end_time)
                            return 'Selesai';
                        return 'Berlangsung';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Belum Mulai' => 'gray',
                        'Berlangsung' => 'success',
                        'Selesai' => 'danger',
                        default => 'gray'
                    }),
            ])
            ->filters([
                // Filter Multi-Select untuk Kelas
                SelectFilter::make('classrooms')
                    ->label('Filter Kelas')
                    ->multiple() // Memungkinkan pilih lebih dari satu kelas
                    ->preload()  // Memuat data di awal agar user tinggal pilih
                    ->relationship('classrooms', 'name')
                    ->searchable(),

                // Filter untuk Status Waktu
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'belum_mulai' => 'Belum Mulai',
                        'berlangsung' => 'Berlangsung',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $now = now();
                        return $query->when($data['value'], function ($query, $value) use ($now) {
                            if ($value === 'belum_mulai') {
                                return $query->where('start_time', '>', $now);
                            }
                            if ($value === 'berlangsung') {
                                return $query->where('start_time', '<=', $now)
                                    ->where('end_time', '>=', $now);
                            }
                            if ($value === 'selesai') {
                                return $query->where('end_time', '<', $now);
                            }
                        });
                    }),
            ])
            ->filtersFormColumns(2)
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->actions([
                // Merapikan semua tombol ke dalam satu Dropdown "Aksi"
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('manageQuestions')
                        ->label('Kelola Soal')
                        ->icon('heroicon-o-academic-cap')
                        ->color('info')
                        ->url(fn(Exam $record): string => static::getUrl('edit', ['record' => $record]) . '#questions'),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(ActionSize::Small)
                    ->color('gray')
                    ->button(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'classrooms.major',
                'category',
                'subject'
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
