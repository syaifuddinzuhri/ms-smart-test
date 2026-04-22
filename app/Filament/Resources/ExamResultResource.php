<?php

namespace App\Filament\Resources;

use App\Enums\ExamSessionStatus;
use App\Filament\Resources\ExamResultResource\Traits\HasResultActions;
use App\Models\Classroom;
use App\Models\ExamCategory;
use App\Models\ExamClassroom;
use App\Models\ExamSession;
use App\Services\ExamService;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ExamResultResource extends Resource
{
    use HasResultActions;
    protected static ?string $model = ExamSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationLabel = 'Hasil Ujian';

    protected static ?string $pluralModelLabel = 'Hasil Ujian';

    protected static ?int $navigationSort = 5;

    // QUERY UTAMA: Hanya ambil yang statusnya COMPLETED
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', ExamSessionStatus::COMPLETED);
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
                //     ->badge()
                //     ->color('warning')
                //     ->toggleable(isToggledHiddenByDefault: false),

                // TextColumn::make('score_short_answer')
                //     ->label('Jawaban Singkat')
                //     ->numeric(2)
                //     ->badge()
                //     ->color('warning')
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('score_essay')
                //     ->label('Essay')
                //     ->badge()
                //     ->color('warning')
                //     ->numeric(2)
                //     ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_score')
                    ->label('Total')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->numeric(2)
                    ->sortable(),

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

                TextColumn::make('finished_at')
                    ->label('Selesai Pada')
                    ->formatStateUsing(function ($state) {
                        if (!$state)
                            return '-';

                        return '
                            <div class="leading-tight">
                                <div>' . $state->format('d/m/Y') . '</div>
                                <div class="text-xs text-gray-500">' . $state->format('H:i:s T') . '</div>
                            </div>
                        ';
                    })
                    ->html()
                    ->sortable(),

                TextColumn::make('finalized_at')
                    ->label('Status Final')
                    ->sortable()
                    ->alignCenter() // Tambahkan ini agar lebih rapi
                    ->state(function ($record) {
                        // Kita ambil langsung dari record agar lebih pasti
                        $date = $record->finalized_at;

                        if (blank($date)) {
                            return new HtmlString('
                                <div class="flex items-center justify-center gap-2 text-amber-500">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-tight">Belum Final</span>
                                </div>
                            ');
                        }

                        // Jika data ada, pastikan dia Carbon
                        $formattedDate = $date instanceof Carbon ? $date : Carbon::parse($date);

                        return new HtmlString('
                            <div class="leading-tight text-center">
                                <div class="flex items-center justify-center gap-1 text-green-600 font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>' . $formattedDate->format('d/m/Y') . '</span>
                                </div>
                                <div class="text-[10px] text-gray-400">' . $formattedDate->format('H:i:s T') . '</div>
                            </div>
                        ');
                    })
            ])
            ->filters([
                SelectFilter::make('exam_category')
                    ->label('Kategori Ujian')
                    ->options(ExamCategory::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('exam', fn($q) => $q->where('exam_category_id', $data['value']));
                        }
                    }),

                SelectFilter::make('classroom')
                    ->label('Kelas')
                    ->options(Classroom::pluck('code', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('user.student', fn($q) => $q->where('classroom_id', $data['value']));
                        }
                    }),

                SelectFilter::make('status_final')
                    ->label('Status Koreksi')
                    ->placeholder('Semua Status')
                    ->options([
                        'pending' => '⏳ Belum Final',
                        'finalized' => '✅ Sudah Final',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'pending') {
                            $query->whereNull('finalized_at');
                        } elseif ($data['value'] === 'finalized') {
                            $query->whereNotNull('finalized_at');
                        }
                    }),
            ], layout: FiltersLayout::Modal)
            ->actions(
                static::getMonitoringTableActions()
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make(
                    static::getMonitoringBulkActions() // Memanggil method baru dari Trait
                ),
            ])
            ->extremePaginationLinks()
            ->poll('5s')
            ->defaultSort('last_activity', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Detail Hasil Ujian')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Informasi Ujian')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Infolists\Components\Section::make('Informasi Peserta')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('user.name')->label('Nama Lengkap')->weight(FontWeight::Bold),
                                        Infolists\Components\TextEntry::make('user.student')
                                            ->label('Kelas')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state || !$state->classroom)
                                                    return '-';

                                                return $state->classroom->name . ' - ' . $state->classroom->major?->name;
                                            })->weight(FontWeight::Bold),
                                        Infolists\Components\TextEntry::make('exam.title')->label('Nama Ujian')->weight(FontWeight::Bold),
                                        Infolists\Components\TextEntry::make('exam.subject.name')->label('Mata Pelajaran')->weight(FontWeight::Bold),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Rincian Nilai')
                                    ->schema([
                                        // Baris 1: Detail per jenis soal
                                        Infolists\Components\TextEntry::make('score_pg')
                                            ->label('Pilihan Ganda')
                                            ->weight(FontWeight::Bold),
                                        Infolists\Components\TextEntry::make('score_short_answer')
                                            ->label('Jawaban Singkat')
                                            ->weight(FontWeight::Bold),
                                        Infolists\Components\TextEntry::make('score_essay')
                                            ->label('Essay')
                                            ->weight(FontWeight::Bold),

                                        // Baris 2: Informasi Ambang Batas & Skor Maksimal
                                        Infolists\Components\TextEntry::make('min_score')
                                            ->label('KKM (Min. Skor)')
                                            ->getStateUsing(function ($record) {
                                                return ExamClassroom::where('exam_id', $record->exam_id)
                                                    ->where('classroom_id', $record->user->student?->classroom_id)
                                                    ->value('min_total_score') ?? '-';
                                            })
                                            ->color('gray')
                                            ->weight(FontWeight::Bold),

                                        Infolists\Components\TextEntry::make('exam.target_max_score')
                                            ->label('Skor Maksimal')
                                            ->color('gray')
                                            ->placeholder('-')
                                            ->weight(FontWeight::Bold),

                                        // Baris 3: Hasil Akhir & Status (Diberi Highlight)
                                        Infolists\Components\TextEntry::make('total_score')
                                            ->label('Skor Akhir')
                                            ->weight(FontWeight::Bold)
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->color(function ($record) {
                                                $passingGrade = ExamClassroom::where('exam_id', $record->exam_id)
                                                    ->where('classroom_id', $record->user->student?->classroom_id)
                                                    ->value('min_total_score');

                                                return (is_null($passingGrade) || $record->total_score >= $passingGrade)
                                                    ? 'success'
                                                    : 'danger';
                                            }),

                                        Infolists\Components\TextEntry::make('status_kelulusan')
                                            ->label('Status')
                                            ->getStateUsing(function ($record) {
                                                $passingGrade = ExamClassroom::where('exam_id', $record->exam_id)
                                                    ->where('classroom_id', $record->user->student?->classroom_id)
                                                    ->value('min_total_score');

                                                $isPassed = is_null($passingGrade) || ($record->total_score >= $passingGrade);

                                                return $isPassed ? 'LULUS' : 'TIDAK LULUS';
                                            })
                                            ->badge()
                                            ->icon(fn(string $state): string => match ($state) {
                                                'LULUS' => 'heroicon-m-check-badge',
                                                'TIDAK LULUS' => 'heroicon-m-x-circle',
                                                default => 'heroicon-m-question-mark-circle',
                                            })
                                            ->color(fn(string $state): string => match ($state) {
                                                'LULUS' => 'success',
                                                'TIDAK LULUS' => 'danger',
                                                default => 'gray',
                                            })
                                            ->weight(FontWeight::Bold),

                                        Infolists\Components\TextEntry::make('finalized_at')
                                            ->label('Status Finalisasi')
                                            ->state(function ($record) {
                                                if (is_null($record->finalized_at)) {
                                                    return new HtmlString('
                                                        <div class="flex flex-col gap-1.5">
                                                            <div class="inline-flex items-center w-fit px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950 dark:text-amber-400 dark:border-amber-800 uppercase tracking-wider">
                                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                                                BELUM FINAL
                                                            </div>
                                                            <span class="text-[10px] text-gray-400 italic font-medium leading-none">Menunggu finalisasi pengawas</span>
                                                        </div>
                                                    ');
                                                }

                                                return new HtmlString('
                                                    <div class="flex items-center gap-1.5">
                                                        <div class="inline-flex items-center w-fit px-2 py-0.5 rounded-md text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-950 dark:text-green-400 dark:border-green-800 uppercase tracking-wider">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                            SUDAH FINAL
                                                        </div>
                                                        <div class="leading-none flex flex-col gap-0.5">
                                                            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200">' . $record->finalized_at->format('d/m/Y') . '</span>
                                                            <span class="text-[10px] text-gray-500 font-medium">' . $record->finalized_at->format('H:i:s T') . '</span>
                                                        </div>
                                                    </div>
                                                ');
                                            })
                                    ])->columns(4), // Menggunakan 4 kolom agar tata letak lebih rapi (akan wrap otomatis)
                                Infolists\Components\Section::make('Waktu & Aktivitas')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('started_at')->label('Waktu Mulai')->dateTime('d/m/Y, H:i:s T'),
                                        Infolists\Components\TextEntry::make('finished_at')->label('Waktu Selesai')->dateTime('d/m/Y, H:i:s T'),
                                        Infolists\Components\TextEntry::make('durasi')
                                            ->label('Durasi Pengerjaan')
                                            ->getStateUsing(function ($record) {
                                                if (!$record->started_at || !$record->finished_at)
                                                    return '-';

                                                $start = Carbon::parse($record->started_at);
                                                $end = Carbon::parse($record->finished_at);

                                                return $start->diff($end)->format('%H:%I:%S');
                                            })->icon('heroicon-o-clock'),
                                        Infolists\Components\TextEntry::make('violation_count')->label('Jumlah Pelanggaran')->badge()->color('danger'),
                                        Infolists\Components\TextEntry::make('ip_address')->label('Alamat IP'),
                                        Infolists\Components\TextEntry::make('device_type')->label('Jenis Perangkat'),
                                    ])->columns(3),
                            ]),
                        Infolists\Components\Tabs\Tab::make('Hasil Jawaban')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Infolists\Components\ViewEntry::make('exam_results')
                                    ->view('filament.infolists.exam-result-detail')
                                    ->columnSpanFull(),
                            ])
                    ])->columnSpanFull(),
            ]);
    }

    public static function exportPdf($record)
    {
        $exam = $record->exam;
        $session = $record;

        $passingGrade = ExamClassroom::where('exam_id', $exam->id)
            ->where('classroom_id', $record->user->student?->classroom_id)
            ->value('min_total_score') ?? 0;

        $isPassed = $record->total_score >= $passingGrade;

        $results = app(ExamService::class)->getQuestions($exam, $session, false);


        $imagePath = public_path('images/logo.webp');
        $logoBase64 = '';

        if (file_exists($imagePath)) {
            $type = pathinfo($imagePath, PATHINFO_EXTENSION);
            $data = file_get_contents($imagePath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }


        // Fungsi untuk mengubah LaTeX menjadi tag IMG (CodeCogs)
        $convertLatex = function ($text) {
            if (empty($text))
                return $text;
            // Gunakan http (bukan https) untuk menghindari masalah SSL DomPDF
            return preg_replace_callback('/(\$\$|\$)(.*?)(\$\$|\$)/s', function ($match) {
                $tex = rawurlencode(trim($match[2]));
                return "<img class='latex-img' src='http://latex.codecogs.com/png.latex?\huge&space;{$tex}' style='vertical-align:middle;'>";
            }, $text);
        };

        $processHtml = function ($text) use ($convertLatex) {
            if (empty($text))
                return $text;

            $text = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/', function ($match) {
                $src = $match[1];

                // Jika src adalah path relatif /storage/..., ubah ke path fisik
                if (str_starts_with($src, '/storage/')) {
                    $path = public_path($src);
                } else {
                    $path = $src; // Gunakan path yang sudah absolut
                }

                // Jika file fisik ada, ubah ke Base64
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    return str_replace($src, $base64, $match[0]);
                }

                return $match[0]; // Jika file tidak ada, biarkan apa adanya
            }, $text);

            return $convertLatex($text);
        };


        $numToAlpha = function ($n) {
            $r = '';
            for ($n += 1; $n > 0; $n = intval(($n - 1) / 26)) {
                $r = chr(65 + ($n - 1) % 26) . $r;
            }
            return $r;
        };
        // Proses semua konten (Soal, Opsi, dan Jawaban)
        $arabic = new Arabic();

        $finalProcessor = function ($text) use ($arabic, $processHtml, $numToAlpha) {
            if (empty($text))
                return $text;

            // STEP A: Jalankan proses HTML & LaTeX terlebih dahulu
            // Sekarang $text berisi tag <img src="..."> untuk gambar dan LaTeX
            $textWithTags = $processHtml($text);

            // STEP B: Proteksi Tag HTML agar tidak dirusak oleh Ar-PHP
            $placeholders = [];
            $protectedText = preg_replace_callback('/<[^>]+>/', function ($matches) use (&$placeholders, $numToAlpha) {
                // Kita gunakan prefix 'PROTECTED' + Huruf (A, B, C...)
                $id = 'PROTECTED' . $numToAlpha(count($placeholders));
                $placeholders[$id] = $matches[0];
                return $id;
            }, $textWithTags);

            // STEP C: Jalankan Arabic Shaping HANYA pada teks yang tersisa
            // Ar-PHP tidak akan menyentuh [[TAG_0]], [[TAG_1]], dst.
            $shapedText = $arabic->utf8Glyphs($protectedText);

            // STEP D: Kembalikan tag asli ke posisinya
            $finalHtml = strtr($shapedText, $placeholders);

            // STEP E: Deteksi Karakter Arab dan bungkus dengan class .arabic-font (seperti sebelumnya)
            return preg_replace_callback('/([\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+)/u', function ($matches) {
                return '<span class="arabic-font">' . $matches[1] . '</span>';
            }, $finalHtml);
        };

        foreach ($results as &$item) {
            $item['question'] = $finalProcessor($item['question']);

            if (!empty($item['options'])) {
                foreach ($item['options'] as $key => $option) {
                    $item['options'][$key] = $finalProcessor($option);
                }
            }
        }

        $start = Carbon::parse($record->started_at);
        $end = Carbon::parse($record->finished_at);

        // Pastikan folder cache font ada
        $fontCachePath = storage_path('fonts');
        if (!file_exists($fontCachePath)) {
            mkdir($fontCachePath, 0775, true);
        }

        // Hasilnya harus TRUE
        $pdf = Pdf::loadView('pdf.exam-result', [
            'record' => $record,
            'session' => $session,
            'exam' => $exam,
            'results' => $results,
            'passingGrade' => $passingGrade,
            'isPassed' => $isPassed,
            'duration' => $start->diff($end)->format('%H:%I:%S'),
            'logo' => $logoBase64,
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Amiri',
                'fontDir' => public_path('fonts'),
                'fontCache' => $fontCachePath,
                'chroot' => [
                    public_path('fonts'),
                    base_path(),
                ],
            ]);

        // Gabungkan teks yang ingin dijadikan nama file
        $fileNameString = "hasil ujian {$exam->title} {$record->user->name}";

        // Ubah menjadi slug (lowercase & mengganti spasi/karakter khusus dengan tanda -)
        $cleanFileName = Str::slug($fileNameString) . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $cleanFileName);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ExamResultResource\Pages\ListExamResults::route('/'),
            'view' => \App\Filament\Resources\ExamResultResource\Pages\ViewExamResult::route('/{record}'),
        ];
    }
}
