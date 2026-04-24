<?php

namespace App\Filament\Resources;

use App\Enums\ExamSessionStatus;
use App\Filament\Resources\ExamMonitoringResource\Traits\HasMonitoringActions;
use App\Models\Classroom;
use App\Models\ExamCategory;
use App\Models\ExamSession;
use App\Models\Subject;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\HtmlString;

class ExamMonitoringResource extends Resource
{
    use HasMonitoringActions;

    protected static ?string $model = ExamSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationLabel = 'Monitoring';

    protected static ?string $pluralModelLabel = 'Monitoring Ujian';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', '!=', ExamSessionStatus::COMPLETED);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->user->student?->classroom?->code ?? '-'),

                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->exam->subject?->name),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                // SKOR / NILAI
                // TextColumn::make('score_pg')
                //     ->label('PG')
                //     ->numeric(2)
                //     ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                //     ->toggleable(),

                // TextColumn::make('score_short_answer')
                //     ->label('Isian')
                //     ->numeric(2)
                //     ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                //     ->toggleable(),

                // TextColumn::make('score_essay')
                //     ->label('Essay')
                //     ->numeric(2)
                //     ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                //     ->toggleable(),

                TextColumn::make('total_score')
                    ->label('Prediksi Nilai Akhir')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->alignCenter()
                    ->description(
                        fn($record) =>
                        new HtmlString('<ul class="text-[10px]">
                        <li>
                        ' . ($record->exam->target_max_score
                            ? 'Skala ' . $record->exam->target_max_score
                            : 'Poin Akumulasi') . '</li>
                        <li>Belum termasuk nilai essay</li>
                    </ul>')
                    )
                    ->numeric(2)
                    ->sortable(),

                // WAKTU & AKTIVITAS
                Tables\Columns\TextColumn::make('total_extension')
                    ->label('Tambahan Waktu')
                    ->getStateUsing(function (ExamSession $record) {
                        $logs = $record->extension_log ?? [];
                        if (empty($logs))
                            return '0m';

                        $total = collect($logs)->sum('minutes');
                        return "+{$total}m";
                    })
                    ->badge()
                    ->color('warning')
                    ->description(function (ExamSession $record) {
                        // Menampilkan histori terakhir sebagai hint
                        $lastLog = collect($record->extension_log)->last();
                        return $lastLog ? "Terakhir: {$lastLog['minutes']}m oleh {$lastLog['by']}" : null;
                    }),
                Tables\Columns\TextColumn::make('sisa_waktu')
                    ->label('Sisa Waktu')
                    ->getStateUsing(function (ExamSession $record) {
                        // 1. Jika sudah selesai, sisa waktu 0
                        if ($record->status === ExamSessionStatus::COMPLETED) {
                            return 0;
                        }

                        // 2. Proteksi jika belum mulai (expires_at masih null)
                        // Tampilkan durasi asli ujian sebagai estimasi awal
                        if (!$record->expires_at) {
                            return ($record->exam?->duration ?? 0) * 60;
                        }

                        // 3. Ambil deadline (jatah individu vs gerbang global)
                        $expiresAt = $record->expires_at;
                        $globalEnd = $record->exam?->end_time;

                        // Tentukan target waktu terkecil (mana yang lebih dulu habis)
                        // Jika globalEnd null, gunakan expiresAt saja
                        $target = $globalEnd ? $expiresAt->min($globalEnd) : $expiresAt;

                        $remaining = now()->diffInSeconds($target, false);

                        return max(0, (int) $remaining);
                    })
                    ->formatStateUsing(function ($state, ExamSession $record) {
                        // Jika status belum mulai, beri keterangan "Estimasi" atau biarkan format jam
                        $formatted = gmdate('H:i:s', $state);
                        return $record->expires_at ? $formatted : "± " . $formatted;
                    })
                    ->color(fn($state) => $state < 300 ? 'danger' : 'success')
                    ->weight(FontWeight::Bold),

                TextColumn::make('last_activity')
                    ->label('Aktif Terakhir')
                    ->dateTime('d/m/Y H:i:s T')
                    ->description(fn($record) => $record->last_activity?->diffForHumans())
                    ->toggleable(),

                // PELANGGARAN
                TextColumn::make('violation_count')
                    ->label('Pelanggaran')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),

                // INFO PERANGKAT (Hidden by Default)
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('device_type')
                    ->label('Device')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 1. FILTER KATEGORI UJIAN (Relasi: ExamSession -> Exam -> Category)
                SelectFilter::make('exam_category')
                    ->label('Kategori Ujian')
                    ->options(ExamCategory::pluck('name', 'id')) // Ambil data langsung dari model Category
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('exam', fn($q) => $q->where('exam_category_id', $data['value']));
                        }
                    }),

                // 2. FILTER KELAS (Relasi: ExamSession -> User -> Student -> Classroom)
                SelectFilter::make('classroom')
                    ->label('Kelas')
                    ->options(Classroom::all()->mapWithKeys(function ($classroom) {
                        $label = "{$classroom->code}";
                        return [$classroom->id => $label];
                    }))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('user.student', fn($q) => $q->where('classroom_id', $data['value']));
                        }
                    }),

                // 3. FILTER MATA PELAJARAN (Relasi: ExamSession -> Exam -> Subject)
                SelectFilter::make('subject')
                    ->label('Mata Pelajaran')
                    ->options(Subject::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('exam', fn($q) => $q->where('subject_id', $data['value']));
                        }
                    }),

                // 4. FILTER MULTIPLE STATUS
                SelectFilter::make('status')
                    ->label('Status Sesi')
                    ->options(ExamSessionStatus::withoutCompleted())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->where('status', $data['value']);
                        }
                    }),

                // 5. FILTER PELANGGARAN
                TernaryFilter::make('has_violations')
                    ->label('Ada Pelanggaran')
                    ->placeholder('Semua Data')
                    ->trueLabel('Hanya Yang Melanggar')
                    ->falseLabel('Tanpa Pelanggaran')
                    ->queries(
                        true: fn(Builder $query) => $query->where('violation_count', '>', 0),
                        false: fn(Builder $query) => $query->where('violation_count', 0),
                    ),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions(
                static::getMonitoringTableActions()
            )
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make(
            //         static::getMonitoringBulkActions() // Memanggil method baru dari Trait
            //     ),
            // ])
            ->extremePaginationLinks()
            ->poll('5s')
            ->defaultSort('last_activity', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ExamMonitoringResource\Pages\ListExamMonitorings::route('/'),
        ];
    }
}
