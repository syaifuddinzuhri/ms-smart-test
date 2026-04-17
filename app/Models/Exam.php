<?php

namespace App\Models;

use App\Enums\ExamStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'status' => ExamStatus::class,
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'can_resume' => 'boolean',
        'is_graded' => 'boolean',
        'show_result_to_student' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($exam) {
            $exam->classrooms()->detach();
            $exam->tokens()->delete();
            $exam->questions()->delete();
            $exam->sessions->each(function ($session) {
                $session->answers->each(function ($answer) {
                    $answer->options()->delete();
                    $answer->delete();
                });
                $session->answers()->delete();
                $session->delete();
            });
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'exam_classrooms')
            ->using(ExamClassroom::class);
    }
    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('order');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('order')
            ->orderBy('exam_questions.order');
    }
    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }
}
