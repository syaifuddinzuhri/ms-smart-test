<?php

namespace App\Models;

use App\Enums\ExamSessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'status' => ExamSessionStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_violation_at' => 'datetime',
        'last_activity' => 'datetime',
        'violation_log' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }
}
