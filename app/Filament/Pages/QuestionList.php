<?php

namespace App\Filament\Pages;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithPagination;

class QuestionList extends Page implements HasActions
{
    use WithPagination, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Daftar Soal';
    protected static ?string $title = 'Daftar Soal';
    protected static ?string $navigationGroup = 'Manajemen Soal';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.question-list';

    public ?array $filters = [
        'subject_id' => null,
        'question_category_id' => null,
    ];

    protected function queryString()
    {
        return [
            'pgPage' => ['except' => 1],
            'shortPage' => ['except' => 1],
            'essayPage' => ['except' => 1],
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('filters.subject_id')
                    ->label('Mata Pelajaran')
                    ->options(Subject::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                Select::make('filters.question_category_id')
                    ->label('Topik')
                    ->options(QuestionCategory::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
            ])
            ->columns(2);
    }

    public function getQuestionsQuery()
    {
        if (!$this->filters['subject_id'] || !$this->filters['question_category_id']) {
            return Question::query()->whereRaw('1=0');
        }

        return Question::query()
            ->with([
                'options' => function ($query) {
                    $query->orderBy('order', 'asc');
                },
                'attachments'
            ])
            ->where('subject_id', $this->filters['subject_id'])
            ->where('question_category_id', $this->filters['question_category_id']);
    }

    public function updatedFilters()
    {
        $this->resetPage('pgPage');
        $this->resetPage('shortPage');
        $this->resetPage('essayPage');
    }

    public function getPgQuestions()
    {
        return $this->getQuestionsQuery()
            ->clone()
            ->whereIn('question_type', [
                QuestionType::SINGLE_CHOICE->value,
                QuestionType::MULTIPLE_CHOICE->value,
                QuestionType::TRUE_FALSE->value
            ])
            ->paginate(10, ['*'], 'pgPage');
    }

    public function getShortQuestions()
    {
        return $this->getQuestionsQuery()
            ->clone()
            ->where('question_type', QuestionType::SHORT_ANSWER->value)
            ->paginate(10, ['*'], 'shortPage');
    }

    public function getEssayQuestions()
    {
        return $this->getQuestionsQuery()
            ->clone()
            ->where('question_type', QuestionType::ESSAY->value)
            ->paginate(10, ['*'], 'essayPage');
    }

    public function getSummary()
    {
        if (!$this->filters['subject_id'] || !$this->filters['question_category_id']) {
            return [
                'pg' => 0,
                'short' => 0,
                'essay' => 0,
                'total' => 0,
            ];
        }

        $base = $this->getQuestionsQuery()->clone();

        return [
            'pg' => (clone $base)->whereIn('question_type', [QuestionType::SINGLE_CHOICE->value, QuestionType::MULTIPLE_CHOICE->value, QuestionType::TRUE_FALSE->value])->count(),
            'short' => (clone $base)->where('question_type', QuestionType::SHORT_ANSWER->value)->count(),
            'essay' => (clone $base)->where('question_type', QuestionType::ESSAY->value)->count(),
            'total' => (clone $base)->count(),
        ];
    }

    public function deleteQuestionAction(): Action
    {
        return Action::make('deleteQuestion')
            ->requiresConfirmation()
            ->modalHeading('Hapus Soal?')
            ->modalDescription('Apakah Anda yakin ingin menghapus soal ini?')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->color('danger')
            ->action(function (array $arguments) {
                $question = Question::find($arguments['id']);

                if ($question) {
                    $question->delete();

                    Notification::make()
                        ->title('Soal berhasil dihapus')
                        ->success()
                        ->send();
                }
            });
    }

    public function getEditUrl($id)
    {
        return EditQuestion::getUrl(['record' => $id]);
    }
}
