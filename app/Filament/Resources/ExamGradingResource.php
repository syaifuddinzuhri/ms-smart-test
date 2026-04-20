<?php

namespace App\Filament\Resources;

use App\Enums\ExamSessionStatus;
use App\Enums\QuestionType;
use App\Filament\Resources\ExamGradingResource\Pages;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Models\Classroom;
use App\Services\ExamService;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ExamGradingResource extends Resource
{
    protected static ?string $model = ExamSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationLabel = 'Penilaian Manual';

    protected static ?string $pluralModelLabel = 'Penilaian Manual Ujian';

    protected static ?int $navigationSort = 5;

    // Hanya tampilkan session yang sudah COMPLETED
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', ExamSessionStatus::COMPLETED);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.student.classroom.name')
                    ->label('Kelas')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->user->student->classroom->name . ' - ' .
                        $record->user->student->classroom->major->name
                    ),

                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->limit(30),

                TextColumn::make('score_essay')
                    ->label('Poin Essay')
                    ->numeric(2)
                    ->badge()
                    ->color('warning'),

                TextColumn::make('total_score')
                    ->label('Total Nilai')
                    ->numeric(2)
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('finished_at')
                    ->label('Selesai Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Filter Berdasarkan Classroom (Name - Major)
                SelectFilter::make('classroom_id')
                    ->label('Filter Kelas')
                    ->options(
                        Classroom::with('major')->get()->mapWithKeys(function ($classroom) {
                            return [$classroom->id => "{$classroom->name} - {$classroom->major->name}"];
                        })
                    )
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('user.student', fn($q) => $q->where('classroom_id', $data['value']));
                        }
                    })
                    ->searchable(),

                // Filter Berdasarkan Ujian
                SelectFilter::make('exam_id')
                    ->label('Filter Ujian')
                    ->options(Exam::all()->pluck('title', 'id'))
                    ->searchable(),
            ])
            ->actions([
                // AKSI UTAMA: Menilai Soal Manual (Essay & Short Answer)
                Action::make('grade')
                    ->label('Koreksi Jawaban')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->modalWidth('4xl')
                    ->modalHeading(fn($record) => new HtmlString("
                    <div class='text-left'>
                        <h2 class='text-xl font-bold tracking-tight'>Koreksi Jawaban: {$record->user->name}</h2>
                        <p class='text-sm font-medium text-gray-500 dark:text-gray-400 mt-1'>
                            <span class='px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-gray-600 dark:text-gray-300'>
                                {$record->exam->title}
                            </span>
                        </p>
                    </div>
                "))
                    // Mengambil hanya jawaban Essay & Short Answer
                    ->form(function (ExamSession $record) {
                        $answers = $record->answers()
                            ->whereHas('question', function ($q) {
                                $q->whereIn('question_type', [QuestionType::SHORT_ANSWER, QuestionType::ESSAY]);
                            })->get();

                        $fields = [];

                        $fields = [
                            Forms\Components\Placeholder::make('instruction')
                                ->label('')
                                ->content(new HtmlString("
                                    <div class='flex p-4 mb-4 text-sm text-gray-800 rounded-lg bg-gray-50 border border-gray-100' role='alert'>
                                        <svg class='flex-shrink-0 inline w-4 h-4 me-3 mt-[2px]' aria-hidden='true' xmlns='http://www.w3.org/2000/svg' fill='currentColor' viewBox='0 0 20 20'>
                                            <path d='M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z'/>
                                        </svg>
                                        <div>
                                            <span class='font-bold italic underline mb-1 block'>Panduan Penilaian Manual:</span>
                                            <ul class='mt-1.5 list-disc list-inside space-y-1'>
                                                <li><span class='font-bold text-blue-900'>Jawaban Singkat:</span> Sistem sudah melakukan pencocokan teks secara otomatis. Anda hanya perlu memverifikasi jika ada ambiguitas.</li>
                                                <li><span class='font-bold text-amber-900'>Essay:</span> Cukup masukkan skor angka. Sistem akan otomatis menandai soal sebagai <span class='italic'>Benar</span> jika skor di atas 0, dan <span class='italic'>Salah</span> jika skor 0.</li>
                                            </ul>
                                        </div>
                                    </div>
                                "))
                        ];

                        foreach ($answers as $index => $answer) {
                            $isEssay = $answer->question->isEssay();

                            $fields[] = Forms\Components\Section::make(new HtmlString("
    <div class='flex justify-between items-center w-full pr-6'>
        <span class='font-bold'>Soal #" . ($index + 1) . "</span>
        <span class='text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider font-black " .
                                ($answer->question->isEssay()
                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400') . "'>
            " . ($answer->question->isEssay() ? 'Essay' : 'Jawaban Singkat') . "
        </span>
    </div>
"))
                                ->description(new HtmlString(
                                    "<div class='prose max-w-none text-gray-800 soal-content'>{$answer->question->question_text}</div>"
                                ))
                                ->schema([

                                    Forms\Components\Placeholder::make('jawaban_siswa')
                                        ->label('Jawaban Siswa:')
                                        ->content(
                                            new HtmlString(
                                                "<div class='prose max-w-none text-gray-800 soal-content'>{$answer->answer_text}</div>"
                                            )
                                        ),

                                    Forms\Components\Placeholder::make('kunci_jawaban')
                                        ->label('Kunci Jawaban (Referensi):')
                                        ->visible($answer->question->isShortAnswer()) // Hanya muncul di Jawaban Singkat
                                        ->content(function () use ($answer) {
                                            // Pecah kunci jawaban jika ada lebih dari satu (separator |)
                                            $keys = explode('|', $answer->question->correct_answer_text ?? '');
                                            $badges = collect($keys)->map(function ($key) {
                                                return "<span class='inline-block px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded border border-green-200 dark:border-green-800 text-[11px] font-bold mr-1 mb-1 uppercase tracking-tight'>" . trim($key) . "</span>";
                                            })->implode('');

                                            return new HtmlString("<div class='mt-1 flex flex-wrap'>{$badges}</div>");
                                        }),

                                    Forms\Components\Grid::make(2)->schema([
                                        ToggleButtons::make("status_{$answer->id}")
                                            ->label('Validasi Jawaban')
                                            ->options([
                                                '1' => 'Benar',
                                                '0' => 'Salah',
                                            ])
                                            ->colors(['1' => 'success', '0' => 'danger'])
                                            ->icons(['1' => 'heroicon-m-check', '0' => 'heroicon-m-x-mark'])
                                            ->default($answer->is_correct === null ? null : ($answer->is_correct ? '1' : '0'))
                                            ->inline()
                                            ->hidden($isEssay),

                                        TextInput::make("score_{$answer->id}")
                                            ->label('Skor (Hanya untuk Essay)')
                                            ->numeric()
                                            ->visible($answer->question->isEssay())
                                            ->placeholder('Max: ' . $record->exam->point_essay_max)
                                            ->default($answer->score),
                                    ])
                                ])->collapsible()
                                ->collapsed();
                        }

                        if ($answers->isEmpty()) {
                            $fields[] = Forms\Components\Placeholder::make('empty')
                                ->content('Tidak ada soal Essay atau Jawaban Singkat untuk dinilai.');
                        }

                        return $fields;
                    })
                    ->action(function (array $data, ExamSession $record) {
                        $examService = app(ExamService::class);

                        $answers = $record->answers()
                            ->whereHas('question', fn($q) => $q->whereIn('question_type', [QuestionType::SHORT_ANSWER, QuestionType::ESSAY]))
                            ->get();

                        \Illuminate\Support\Facades\DB::beginTransaction();

                        try {
                            foreach ($answers as $index => $answer) {
                                $questionNumber = $index + 1;

                                if ($answer->question->isEssay()) {
                                    $essayScore = (float) ($data["score_{$answer->id}"] ?? 0);
                                    $isCorrect = $essayScore > 0;
                                } else {
                                    $isCorrect = ($data["status_{$answer->id}"] ?? null) === '1';
                                    $essayScore = null;
                                }

                                try {
                                    $examService->manualVerify($answer, $isCorrect, $essayScore);
                                } catch (\Exception $e) {
                                    throw new \Exception("Soal nomor {$questionNumber} Terjadi kesalahan|{$e->getMessage()}");
                                }
                            }

                            \Illuminate\Support\Facades\DB::commit();

                            Notification::make()
                                ->title('Penilaian Berhasil')
                                ->body('Seluruh jawaban manual telah diperbarui.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\DB::rollBack();

                            $errorMessage = $e->getMessage();
                            if (str_contains($errorMessage, '|')) {
                                [$title, $body] = explode('|', $errorMessage);
                            } else {
                                $title = 'Terjadi Kesalahan Teknis';
                                $body = $errorMessage;
                            }

                            Notification::make()
                                ->title($title)
                                ->body($body)
                                ->danger()
                                ->send();

                            throw new Halt();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamGradings::route('/'),
        ];
    }
}
