<?php

namespace App\Filament\Student\Pages;

use App\Enums\ExamSessionStatus;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamClassroom;
use App\Models\ExamSession;
use App\Models\Subject;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ResultTest extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationLabel = 'Hasil Ujian';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.student.pages.result-test';

    public function getTitle(): string|Htmlable
    {
        return 'Riwayat Hasil Ujian';
    }

    public function getHeading(): string|Htmlable
    {
        return new HtmlString('
        <div class="flex flex-col gap-1">
            <span class="text-2xl font-extrabold text-gray-900 leading-tight uppercase">
                Riwayat Hasil Ujian
            </span>
        </div>
    ');
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $userId = $user->id;
        $classroomId = $user->student?->classroom_id;

        return $table
            ->query(
                Exam::query()
                    ->whereHas('sessions', function ($query) use ($userId) {
                        $query->where('user_id', $userId)
                            ->where('status', ExamSessionStatus::COMPLETED);
                    })
                    ->addSelect([
                        'finished_at' => ExamSession::select('finished_at')
                            ->whereColumn('exam_id', 'exams.id')
                            ->where('user_id', $userId)
                            ->latest()
                            ->take(1),
                        'student_score' => ExamSession::select('total_score')
                            ->whereColumn('exam_id', 'exams.id')
                            ->where('user_id', $userId)
                            ->latest()
                            ->take(1),
                        'passing_grade' => ExamClassroom::select('min_total_score')
                            ->whereColumn('exam_id', 'exams.id')
                            ->where('classroom_id', $classroomId)
                            ->take(1),
                        'target_classroom' => Classroom::query()
                            ->join('majors', 'classrooms.major_id', '=', 'majors.id')
                            ->where('classrooms.id', $classroomId)
                            ->selectRaw("CONCAT(classrooms.name, ' - ', majors.name)")
                            ->take(1),
                    ])
                    ->with([
                        'sessions' => function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                        'category',
                        'subject'
                    ])
            )
            ->defaultSort('finished_at')
            ->searchable()
            ->filters([
                SelectFilter::make('exam_category_id')
                    ->label('Kategori')
                    ->options(ExamCategory::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->options(Subject::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->columns(self::buildColumns())
            ->actions([
                Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->outlined()
                    ->extraAttributes(['class' => 'w-full md:w-auto mt-4 justify-center'])
                    ->url(fn($record) => DetailResultTest::getUrl([
                        'record' => $record->id
                    ]))
            ]);
    }

    protected static function buildColumns()
    {
        return [
            Stack::make([
                TextColumn::make('title')
                    ->label('Judul')
                    ->weight(FontWeight::Bold)
                    ->size(TextColumn\TextColumnSize::Large),
                Split::make([
                    TextColumn::make('finished_at')
                        ->description(new HtmlString('
                            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 block mb-[-4px]">
                                Waktu Selesai
                            </span>
                        '), position: 'above')
                        ->dateTime('d M Y, H:i T')
                        ->size(TextColumn\TextColumnSize::ExtraSmall)
                        ->color('gray')
                        ->icon('heroicon-m-calendar-days'),
                    TextColumn::make('subject.name')
                        ->formatStateUsing(fn($state) => 'Mata Pelajaran: ' . $state)
                        ->size(TextColumn\TextColumnSize::ExtraSmall)
                        ->color('gray'),
                    TextColumn::make('target_classroom')
                        ->formatStateUsing(fn($state) => 'Kelas: ' . $state)
                        ->size(TextColumn\TextColumnSize::ExtraSmall)
                        ->color('gray'),
                ])->from('md')->extraAttributes(['class' => 'mb-1']),


                Split::make([
                    TextColumn::make('student_score')
                        ->getStateUsing(function ($record) {
                            $score = number_format($record->student_score, 2);
                            $max = $record->target_max_score;
                            if ($max > 0) {
                                return "Skor: {$score} / {$max}";
                            }
                            return "Skor: {$score}";
                        })
                        ->badge()
                        ->color('info')
                        ->weight(FontWeight::Black),

                    TextColumn::make('passing_grade')
                        ->getStateUsing(fn($record) => "KKM / Min. Skor: " . number_format($record->passing_grade, 2))
                        ->badge()
                        ->color('gray')
                        ->icon('heroicon-m-flag'),

                    TextColumn::make('is_lulus')
                        ->getStateUsing(function ($record) {
                            return $record->student_score >= $record->passing_grade ? 'LULUS' : 'TIDAK LULUS';
                        })
                        ->badge()
                        ->color(fn($state) => $state === 'LULUS' ? 'success' : 'danger'),
                ])->extraAttributes(['class' => 'mt-3 mb-2'])
                    ->visible(fn(Exam $record) => $record->show_result_to_student)
            ])->space(2),
        ];
    }
}
