<?php

namespace App\Filament\Resources;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamStatus;
use App\Filament\Resources\ExamResource\Pages;
use App\Helpers\ExamTimeHelper;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Subject;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Closure;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Daftar Ujian';
    protected static ?string $modelLabel = 'Ujian';
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
                                    ->label('Durasi Awal (Menit)')
                                    ->numeric()
                                    ->suffix('Menit')
                                    ->required(),
                                DateTimePicker::make('start_time')
                                    ->label('Waktu Mulai')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->format('Y-m-d H:i:00')
                                    ->seconds(false),

                                DateTimePicker::make('end_time')
                                    ->label('Waktu Selesai')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->format('Y-m-d H:i:00')
                                    ->seconds(false),
                            ])->columns(2),

                        Tab::make('Target Peserta')
                            ->schema([
                                Repeater::make('examClassrooms')
                                    ->label('')
                                    ->schema([
                                        Select::make('classroom_id')
                                            ->label('Kelas')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->distinct()
                                            ->options(function ($get, $state) {
                                                $selected = collect($get('../../examClassrooms') ?? [])
                                                    ->pluck('classroom_id')
                                                    ->filter()
                                                    ->reject(fn($id) => $id === $state);

                                                return \App\Models\Classroom::query()
                                                    ->whereNotIn('id', $selected)
                                                    ->get()
                                                    ->mapWithKeys(fn($item) => [
                                                        $item->id => "{$item->name} - {$item->major?->name}"
                                                    ]);
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                $classroom = \App\Models\Classroom::with('major')->find($value);
                                                return $classroom ? "{$classroom->name} - {$classroom->major?->name}" : null;
                                            }),

                                        TextInput::make('min_total_score')
                                            ->label('KKM / Minimal Lulus')
                                            ->numeric()
                                            ->default(80)
                                            ->inputMode('numeric')
                                            ->minValue(0)
                                            ->step(1)
                                            ->integer()
                                            ->helperText('Nilai minimal untuk lulus di kelas ini.')
                                            ->suffix('Poin'),
                                    ])
                                    ->columns(2)
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah Target')
                                    ->deleteAction(fn($action) => $action->visible(fn($get) => count($get('classrooms') ?? []) > 1))
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Tab::make('Sistem Poin')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        // --- SEKSI PILIHAN GANDA ---
                                        Section::make('Pilihan Ganda')
                                            ->schema([
                                                TextInput::make('point_pg')
                                                    ->label('Poin Benar')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0)
                                                    ->lazy(), // Mengupdate state saat kursor pindah (lebih ringan dari reactive)

                                                TextInput::make('point_pg_wrong')
                                                    ->label('Poin Salah')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('-')
                                                    ->helperText('Pinalti jika jawaban salah.')
                                                    ->rules([
                                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                            $poinBenar = (float) $get('point_pg');
                                                            if ((float) $value > $poinBenar) {
                                                                $fail("Pinalti tidak boleh melebihi poin benar ({$poinBenar})");
                                                            }
                                                        },
                                                    ]),

                                                TextInput::make('point_pg_null')
                                                    ->label('Poin Kosong')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('-')
                                                    ->helperText('Pinalti jika tidak dijawab.')
                                                    ->rules([
                                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                            $poinBenar = (float) $get('point_pg');
                                                            if ((float) $value > $poinBenar) {
                                                                $fail("Pinalti tidak boleh melebihi poin benar ({$poinBenar})");
                                                            }
                                                        },
                                                    ]),
                                            ])->columnSpan(1),

                                        // --- SEKSI JAWABAN SINGKAT ---
                                        Section::make('Jawaban Singkat')
                                            ->schema([
                                                TextInput::make('point_short_answer')
                                                    ->label('Poin Benar')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0)
                                                    ->lazy(),

                                                TextInput::make('point_short_answer_wrong')
                                                    ->label('Poin Salah')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('-')
                                                    ->helperText('Pinalti jika jawaban salah.')
                                                    ->rules([
                                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                            $poinBenar = (float) $get('point_short_answer');
                                                            if ((float) $value > $poinBenar) {
                                                                $fail("Pinalti tidak boleh melebihi poin benar ({$poinBenar})");
                                                            }
                                                        },
                                                    ]),

                                                TextInput::make('point_short_answer_null')
                                                    ->label('Poin Kosong')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('-')
                                                    ->helperText('Pinalti jika tidak dijawab.')
                                                    ->rules([
                                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                            $poinBenar = (float) $get('point_short_answer');
                                                            if ((float) $value > $poinBenar) {
                                                                $fail("Pinalti tidak boleh melebihi poin benar ({$poinBenar})");
                                                            }
                                                        },
                                                    ]),
                                            ])->columnSpan(1),

                                        // --- SEKSI ESSAY ---
                                        Section::make('Essay')
                                            ->schema([
                                                TextInput::make('point_essay_max')
                                                    ->label('Poin Maksimal')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->minValue(1)
                                                    ->lazy(),

                                                TextInput::make('point_essay_null')
                                                    ->label('Poin Kosong')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('-')
                                                    ->helperText('Pinalti jika tidak dijawab.')
                                                    ->rules([
                                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                            $poinMax = (float) $get('point_essay_max');
                                                            if ((float) $value > $poinMax) {
                                                                $fail("Pinalti tidak boleh melebihi poin maksimal ({$poinMax})");
                                                            }
                                                        },
                                                    ]),
                                            ])->columnSpan(1),
                                    ]),
                            ]),
                        Tab::make('Pengaturan & Keamanan')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Metode Pengacakan')
                                            ->description('Tentukan bagaimana sistem mengacak komponen ujian.')
                                            ->schema([
                                                Select::make('random_question_type')
                                                    ->label('Acak Urutan Soal')
                                                    ->options([
                                                        0 => 'Matikan (Urutan Tetap)',
                                                        1 => 'Individu (Tiap peserta mendapatkan urutan berbeda)',
                                                        2 => 'Massal (Satu urutan acak untuk semua peserta)',
                                                    ])
                                                    ->default(0)
                                                    ->native(false)
                                                    ->selectablePlaceholder(false),

                                                \Filament\Forms\Components\Toggle::make('random_option_type')
                                                    ->label('Acak Pilihan Jawaban')
                                                    ->helperText('Jika aktif, urutan opsi (A, B, C, D, E) akan diacak untuk setiap peserta.')
                                                    ->default(false),
                                            ])->columnSpan(1),

                                        Section::make('Hasil & Transparansi')
                                            ->description('Pengaturan pasca ujian selesai.')
                                            ->schema([
                                                \Filament\Forms\Components\Toggle::make('show_result_to_student')
                                                    ->label('Tampilkan Nilai ke Peserta')
                                                    ->helperText('Peserta dapat melihat skor akhir setelah mereka menyelesaikan ujian.')
                                                    ->default(false),
                                                TextInput::make('target_max_score')
                                                    ->label('Skala Skor Akhir')
                                                    ->numeric()
                                                    ->default(100)
                                                    ->nullable() // Mengizinkan field dikosongkan (null)
                                                    ->minValue(100) // Jika diisi, minimal 100
                                                    ->maxValue(1000) // Jika diisi, maksimal 1000
                                                    ->placeholder('Contoh: 100') // Memberi petunjuk visual
                                                    ->helperText('Kosongkan jika ingin menggunakan poin akumulasi mentah. Jika diisi (min: 100, max: 1000), nilai akhir akan dikonversi otomatis ke skala ini (misal: Skala 100).')
                                            ])->columnSpan(1),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->disabled(fn(?Exam $record) => $record && $record?->status !== ExamStatus::DRAFT);
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
            ->columns(self::buildColumns())
            ->filters([
                SelectFilter::make('classrooms')
                    ->label('Filter Kelas')
                    ->multiple()
                    ->preload()
                    ->relationship('classrooms', 'name')
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options(ExamStatus::class)
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->filtersFormColumns(2)
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('manageQuestions')
                        ->label('Kelola Soal')
                        ->icon('heroicon-o-academic-cap')
                        ->color('gray')
                        ->url(fn(Exam $record): string => static::getUrl('manage-questions', ['record' => $record])),

                    Tables\Actions\Action::make('manageTokens')
                        ->label('Kelola Token')
                        ->icon('heroicon-o-key')
                        ->visible(fn(Exam $record) => $record->status !== ExamStatus::CLOSED)
                        ->color('gray')
                        ->url(fn(Exam $record): string => static::getUrl('manage-tokens', ['record' => $record])),

                    Tables\Actions\Action::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-m-arrow-path')
                        ->color('gray')
                        ->modalWidth(MaxWidth::Medium)
                        ->modalHeading('Perbarui Status Ujian')
                        ->modalDescription('Pilih status baru untuk ujian ini. Pastikan transisi status sudah sesuai.')
                        ->modalSubmitAction(
                            fn(\Filament\Actions\StaticAction $action) => $action
                                ->label('Simpan Perubahan')
                                ->color('primary'),
                        )
                        ->form([
                            Select::make('status')
                                ->label('Status Baru')
                                ->options(ExamStatus::class)
                                ->default(fn(Exam $record) => $record->status->value)
                                ->selectablePlaceholder(false)
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),
                            TextInput::make('additional_minutes')
                                ->label('Tambahan Waktu (Menit)')
                                ->numeric()
                                ->suffix('Menit')
                                ->minValue(1)
                                ->helperText('Waktu selesai ujian dan sisa waktu peserta akan bertambah secara massal.')
                                ->visible(
                                    fn(Get $get, Exam $record) =>
                                    $record->status !== ExamStatus::DRAFT && $get('status') !== ExamStatus::DRAFT->value
                                )
                                ->placeholder('Masukkan angka menit...'),
                            TextInput::make('reason')
                                ->label('Alasan Penambahan Waktu')
                                ->hint('Opsional')
                                ->visible(
                                    fn(Get $get, Exam $record) =>
                                    $record->status !== ExamStatus::DRAFT && $get('status') !== ExamStatus::DRAFT->value
                                )
                                ->placeholder('Tidak Ada Alasan'),
                        ])
                        ->action(fn(Exam $record, array $data) => self::handleStatusUpdate($record, $data)),
                    Tables\Actions\Action::make('viewExtensions')
                        ->label('Riwayat Tambahan Waktu')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->modalHeading('Riwayat Tambahan Waktu')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->form(fn(Exam $record) => [
                            Placeholder::make('extension_logs')
                                ->label('')
                                ->content(new HtmlString(static::buildExtensionLogHtml($record))),
                        ]),
                    Tables\Actions\EditAction::make()
                        ->icon(fn($record) => $record->status !== ExamStatus::DRAFT ? 'heroicon-m-lock-closed' : 'heroicon-m-pencil-square')
                        ->label(fn($record) => $record->status !== ExamStatus::DRAFT ? 'Lihat Detail' : 'Edit'),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('toggleShowResult')
                        ->label(
                            fn(Exam $record) => $record->show_result_to_student
                            ? 'Sembunyikan Nilai'
                            : 'Tampilkan Nilai'
                        )
                        ->icon(
                            fn(Exam $record) => $record->show_result_to_student
                            ? 'heroicon-m-eye-slash'
                            : 'heroicon-m-eye'
                        )
                        ->color(
                            fn(Exam $record) => $record->show_result_to_student
                            ? 'danger'
                            : 'success'
                        )
                        ->requiresConfirmation()
                        ->modalHeading(
                            fn(Exam $record) => $record->show_result_to_student
                            ? 'Sembunyikan Hasil Ujian?'
                            : 'Tampilkan Hasil Ujian?'
                        )
                        ->modalDescription(
                            fn(Exam $record) => $record->show_result_to_student
                            ? 'Peserta tidak akan bisa melihat nilai mereka.'
                            : 'Peserta akan bisa melihat nilai mereka.'
                        )
                        ->action(function (Exam $record) {
                            $record->update([
                                'show_result_to_student' => !$record->show_result_to_student,
                            ]);
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(ActionSize::Small)
                    ->color('gray')
                    ->button(),
            ]);
    }

    protected static function buildColumns()
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->label('Judul Ujian')
                ->searchable()
                ->description(function (Exam $record): string {
                    $classrooms = $record->classrooms->map(fn($c) => "{$c->name} {$c->major?->code}");

                    if ($classrooms->count() <= 3) {
                        return $classrooms->implode(', ');
                    }

                    $firstThree = $classrooms->take(3)->implode(', ');
                    $remainingCount = $classrooms->count() - 3;

                    return "{$firstThree} ... (+{$remainingCount} lainnya)";
                }),

            Tables\Columns\TextColumn::make('start_time')
                ->label('Jadwal Pelaksanaan')
                ->icon('heroicon-m-calendar-days')
                ->color('gray')
                ->weight('medium')
                ->formatStateUsing(function (Exam $record) {
                    $start = $record->start_time;
                    $end = $record->end_time;

                    if (!$start || !$end)
                        return '-';

                    if ($start->isSameDay($end)) {
                        return $start->translatedFormat('d F Y');
                    }

                    return $start->translatedFormat('d M Y, H:i T');
                })
                ->description(function (Exam $record): HtmlString {
                    $start = $record->start_time;
                    $end = $record->end_time;

                    if (!$start || !$end)
                        return new HtmlString('');

                    if ($start->isSameDay($end)) {
                        return new HtmlString("
                            <div class='flex items-center gap-1 text-primary-600 font-medium text-xs mt-0.5'>
                                <svg class='w-3.5 h-3.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>
                                <span>{$start->format('H:i')} — {$end->format('H:i T')}</span>
                            </div>
                        ");
                    }

                    return new HtmlString("
                        <div class='text-gray-500 text-xs mt-0.5'>
                            Selesai: <span class='text-gray-500'>{$end->translatedFormat('d M Y, H:i T')}</span>
                        </div>
                    ");
                }),

            Tables\Columns\TextColumn::make('duration')
                ->label('Durasi (Menit)')
                ->getStateUsing(function (Exam $record) {
                    // Durasi Asli + Total Tambahan di log Exam
                    $additional = collect($record->extension_log ?? [])->sum('minutes');
                    return $record->duration + $additional;
                })
                ->numeric()
                ->badge()
                ->color(fn($record) => collect($record->extension_log ?? [])->isNotEmpty() ? 'danger' : 'warning')
                ->description(function (Exam $record) {
                    $additional = collect($record->extension_log ?? [])->sum('minutes');
                    if ($additional <= 0)
                        return null;

                    return new HtmlString(
                        "<span class='text-xs text-success-600 font-medium'>Asli: {$record->duration}m (+{$additional}m)</span>"
                    );
                })
                ->alignCenter(),

            Tables\Columns\TextColumn::make('total_soal')
                ->label('Butir Soal')
                ->getStateUsing(fn(Exam $record) => $record->pg_count + $record->short_count + $record->essay_count)
                ->weight('bold')
                ->badge()
                ->color('success')
                ->icon('heroicon-s-circle-stack')
                ->description(function (Exam $record): HtmlString {
                    return new HtmlString("
                        <div class='flex gap-1.5 mt-1 text-[10px] font-bold uppercase tracking-tighter'>
                            <span class='text-emerald-600'>PG: {$record->pg_count}</span>
                            <span class='text-gray-300'>|</span>
                            <span class='text-blue-600'>Skt: {$record->short_count}</span>
                            <span class='text-gray-300'>|</span>
                            <span class='text-amber-600'>Esy: {$record->essay_count}</span>
                        </div>
                    ");
                })
                ->alignCenter(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge(),
        ];
    }

    protected static function buildExtensionLogHtml(Exam $record): string
    {
        $record->refresh();
        $logs = $record->extension_log ?? [];

        if (empty($logs)) {
            return '<div class="text-sm text-gray-500 italic text-center py-4">Belum ada riwayat tambahan waktu untuk peserta ini.</div>';
        }

        $html = "<div class='space-y-3'>";
        foreach (array_reverse($logs) as $log) {
            $time = Carbon::parse($log['at'])->format('d/m/Y H:i T');
            $minutes = $log['minutes'] ?? 0;
            $admin = $log['by'] ?? 'System';
            $reason = $log['reason'] ?? 'Tidak ada alasan';

            $html .= "
                <div class='p-3 bg-white border border-gray-200 rounded-lg shadow-sm'>
                    <div class='flex justify-between items-start mb-2'>
                        <span class='px-2 py-1 bg-success-50 text-success-700 text-xs font-bold rounded'>+{$minutes} Menit</span>
                        <span class='text-[10px] text-gray-400 font-mono'>$time</span>
                    </div>
                    <div class='text-xs text-gray-600 mb-1'>
                        <span class='font-semibold text-gray-800'>Oleh:</span> $admin
                    </div>
                    <div class='text-xs text-gray-500 italic'>
                        \"$reason\"
                    </div>
                </div>";
        }
        $html .= "</div>";

        return $html;
    }

    protected static function handleStatusUpdate(Exam $record, array $data): void
    {
        $oldStatus = $record->status;
        $newStatus = ExamStatus::from($data['status']);
        $additionalMinutes = (int) ($data['additional_minutes'] ?? 0);

        // 1. Validasi Transisi Status (Early Return jika tidak valid)
        if ($oldStatus === $newStatus && $additionalMinutes <= 0) {
            return;
        }

        $allowedTransitions = match ($oldStatus) {
            ExamStatus::DRAFT => [ExamStatus::ACTIVE, ExamStatus::INACTIVE, ExamStatus::CLOSED],
            ExamStatus::ACTIVE => [ExamStatus::INACTIVE, ExamStatus::CLOSED, ExamStatus::DRAFT],
            ExamStatus::INACTIVE => [ExamStatus::ACTIVE, ExamStatus::CLOSED, ExamStatus::DRAFT],
            ExamStatus::CLOSED => [ExamStatus::ACTIVE],
            default => [],
        };

        if ($newStatus !== $oldStatus && !in_array($newStatus, $allowedTransitions)) {
            Notification::make()->title('Transisi Status Gagal')->danger()
                ->body("Status {$oldStatus->getLabel()} tidak bisa diubah langsung ke {$newStatus->getLabel()}.")->send();
            return;
        }

        // 2. Validasi Kelengkapan Soal
        if ($newStatus === ExamStatus::ACTIVE && !$record->examQuestions()->exists()) {
            Notification::make()->title('Gagal Mengaktifkan')->danger()
                ->body('Ujian belum memiliki soal. Isi minimal 1 soal sebelum diaktifkan.')->send();
            return;
        }

        // 3. Validasi Kembali ke Draft
        if ($newStatus === ExamStatus::DRAFT && $record->sessions()->exists()) {
            Notification::make()->title('Gagal Kembali ke Draft')->warning()
                ->body('Ujian tidak bisa kembali ke Draft karena sudah diakses oleh peserta.')->send();
            return;
        }

        // 4. Proses Database dengan Transaction
        DB::transaction(function () use ($record, $newStatus, $additionalMinutes, $data) {
            $messageSuffix = "";

            // Logika Penambahan Waktu
            if ($additionalMinutes > 0) {
                ExamTimeHelper::extendAllSessions($record, $additionalMinutes, $data['reason']);
                $record->status = ExamStatus::ACTIVE;
                $messageSuffix = " Serta sisa waktu semua peserta bertambah {$additionalMinutes} menit.";
            } else {
                $record->status = $newStatus;
            }

            if ($record->status === ExamStatus::INACTIVE) {
                $record->sessions()
                    ->whereIn('status', [ExamSessionStatus::ONGOING])
                    ->update([
                        'status' => ExamSessionStatus::PAUSE,
                        'token' => null,
                        'system_id' => null
                    ]);
            }

            $record->save();

            Notification::make()
                ->title('Status Diperbarui')
                ->success()
                ->body("Ujian kini berstatus {$record->status->getLabel()}.{$messageSuffix}")
                ->send();
        });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'classrooms.major',
                'category',
                'subject'
            ])
            ->withCount([
                'questions as pg_count' => function (Builder $query) {
                    $query->whereIn('question_type', [
                        \App\Enums\QuestionType::SINGLE_CHOICE->value,
                        \App\Enums\QuestionType::MULTIPLE_CHOICE->value,
                        \App\Enums\QuestionType::TRUE_FALSE->value,
                    ]);
                },
                'questions as short_count' => function (Builder $query) {
                    $query->where('question_type', \App\Enums\QuestionType::SHORT_ANSWER->value);
                },
                'questions as essay_count' => function (Builder $query) {
                    $query->where('question_type', \App\Enums\QuestionType::ESSAY->value);
                },
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
            'manage-questions' => Pages\ManageExamQuestions::route('/{record}/manage-questions'),
            'manage-tokens' => Pages\ManageExamTokens::route('/{record}/manage-tokens'),
        ];
    }
}
