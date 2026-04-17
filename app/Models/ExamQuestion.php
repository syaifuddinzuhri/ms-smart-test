<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'question_type' => QuestionType::class,
    ];
}
