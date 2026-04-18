<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot; // WAJIB PIVOT

class ExamAnswerOption extends Pivot
{
    use HasUuids;
    protected $table = 'exam_answer_options';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
