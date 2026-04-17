<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Enums\QuestionType;
use App\Filament\Resources\ExamResource;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class ManageExamQuestions extends Page
{
    use InteractsWithRecord, WithPagination {
        configureAction as traitConfigureAction;
        afterActionCalled as traitAfterActionCalled;
        getMountedActionFormModel as traitGetMountedActionFormModel;
    }

    protected static string $resource = ExamResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.resources.exam-resource.pages.manage-exam-questions';

    public ?array $filters = [
        'subject_id' => null,
        'question_category_id' => null,
    ];

    protected $queryString = [
        'page' => ['except' => 1],
    ];

    public function mount(string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return "Kelola Soal: " . ($this->record->title ?? 'Ujian');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('filters.subject_id')
                    ->label('Mata Pelajaran')
                    ->options(Subject::pluck('name', 'id'))
                    ->live()
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('filters.question_category_id')
                    ->label('Pilih Topik')
                    ->options(QuestionCategory::pluck('name', 'id'))
                    ->live()
                    ->preload()
                    ->searchable()
                    ->required()
            ])->columns(2);
    }

    public function getAvailableSummary()
    {
        if (!$this->filters['subject_id'] || !$this->filters['question_category_id']) {
            return null;
        }

        $existingQuestionIds = ExamQuestion::where('exam_id', $this->record->id)->pluck('question_id');

        $query = Question::query()
            ->where('subject_id', $this->filters['subject_id'])
            ->where('question_category_id', $this->filters['question_category_id'])
            ->whereNotIn('id', $existingQuestionIds);

        return [
            'pg' => (clone $query)->whereIn('question_type', [QuestionType::SINGLE_CHOICE, QuestionType::MULTIPLE_CHOICE, QuestionType::TRUE_FALSE])->count(),
            'short' => (clone $query)->where('question_type', QuestionType::SHORT_ANSWER)->count(),
            'essay' => (clone $query)->where('question_type', QuestionType::ESSAY)->count(),
            'total' => (clone $query)->count(),
        ];
    }

    public function getExamQuestions()
    {
        return ExamQuestion::query()
            ->where('exam_id', $this->record->id)
            ->join('questions', 'exam_questions.question_id', '=', 'questions.id')
            ->join('question_categories', 'questions.question_category_id', '=', 'question_categories.id')
            ->select(
                'exam_questions.*',
                'questions.question_text',
                'questions.question_type',
                'question_categories.name as category_name'
            )
            ->orderBy('exam_questions.order')
            ->paginate(10);
    }

    public function updatedFilters()
    {
        $this->resetPage();
    }

    public function addQuestions()
    {
        $summary = $this->getAvailableSummary();

        if (!$summary || $summary['total'] === 0) {
            Notification::make()->title('Tidak ada soal baru yang ditemukan')->warning()->send();
            return;
        }

        DB::beginTransaction();
        try {
            $existingQuestionIds = ExamQuestion::where('exam_id', $this->record->id)->pluck('question_id');

            $questions = Question::where('subject_id', $this->filters['subject_id'])
                ->where('question_category_id', $this->filters['question_category_id'])
                ->whereNotIn('id', $existingQuestionIds)
                ->get();

            foreach ($questions as $q) {
                ExamQuestion::create([
                    'id' => Str::uuid(),
                    'exam_id' => $this->record->id,
                    'question_id' => $q->id,
                ]);
            }

            $this->resolveOrder();

            DB::commit();
            Notification::make()->title($questions->count() . ' Soal berhasil dimasukkan ke ujian')->success()->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Terjadi kesalahan')->body($e->getMessage())->danger()->send();
        } finally {
            $this->resetPage();
        }
    }

    /**
     * Fungsi Inti: Mengurutkan ulang semua soal berdasarkan hirarki tipe
     */
    public function resolveOrder()
    {
        // Ambil semua soal yang terhubung dengan ujian ini, join ke tabel questions untuk tahu tipenya
        $allExamQuestions = ExamQuestion::query()
            ->where('exam_id', $this->record->id)
            ->join('questions', 'exam_questions.question_id', '=', 'questions.id')
            ->select('exam_questions.*', 'questions.question_type')
            ->get();

        // Urutkan koleksi di memori berdasarkan hirarki tipe
        $sorted = $allExamQuestions->sort(function ($a, $b) {
            // Tentukan bobot tipe
            $priority = [
                QuestionType::SINGLE_CHOICE->value => 1,
                QuestionType::MULTIPLE_CHOICE->value => 1,
                QuestionType::TRUE_FALSE->value => 1,
                QuestionType::SHORT_ANSWER->value => 2,
                QuestionType::ESSAY->value => 3,
            ];

            $prioA = $priority[$a->question_type->value] ?? 4;
            $prioB = $priority[$b->question_type->value] ?? 4;

            if ($prioA === $prioB) {
                // Jika tipe sama, urutkan berdasarkan waktu buat atau ID (agar konsisten)
                return $a->created_at <=> $b->created_at;
            }

            return $prioA <=> $prioB;
        });

        // Update kolom 'order' secara sekuensial 1, 2, 3...
        DB::transaction(function () use ($sorted) {
            foreach ($sorted->values() as $index => $item) {
                ExamQuestion::where('id', $item->id)->update([
                    'order' => $index + 1
                ]);
            }
        });
    }

    public function removeAllAction(): Action
    {
        return Action::make('removeAll')
            ->label('Kosongkan Semua Soal')
            ->requiresConfirmation()
            ->modalHeading('Kosongkan Daftar Soal?')
            ->modalDescription('Apakah Anda yakin ingin menghapus SELURUH soal dari ujian ini? Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Kosongkan')
            ->color('danger')
            ->action(function () {
                ExamQuestion::where('exam_id', $this->record->id)->delete();
                Notification::make()->title('Semua soal telah dikosongkan')->success()->send();
            });
    }

    public function removeQuestionAction(): Action
    {
        return Action::make('removeQuestion')
            ->requiresConfirmation()
            ->modalHeading('Hapus soal dari ujian?')
            ->modalDescription('Soal akan dihapus dari ujian ini, namun tetap ada di Bank Soal.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->color('danger')
            ->action(function (array $arguments) {
                $questionId = $arguments['question_id'];
                DB::transaction(function () use ($questionId) {
                    ExamQuestion::where('exam_id', $this->record->id)->where('question_id', $questionId)->delete();
                    $this->resolveOrder();
                });

                Notification::make()->title('Soal dihapus dari ujian')->success()->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-m-arrow-left'),
        ];
    }
}
