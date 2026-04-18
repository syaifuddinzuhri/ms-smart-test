<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAnswer extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ExamAnswerOption::class);
    }

    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionOption::class,
            'exam_answer_options',
            'exam_answer_id',
            'question_option_id'
        );
    }
}
