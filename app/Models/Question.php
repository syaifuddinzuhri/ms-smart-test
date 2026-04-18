<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Question extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'question_type' => QuestionType::class,
        'correct_answer' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($question) {
            if ($question->examQuestions()->exists()) {
                throw new \Exception("Soal ini tidak bisa dihapus karena sudah digunakan dalam Ujian.");
            }

            $directoryPath = "questions/{$question->id}";

            if (Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->deleteDirectory($directoryPath);
            }

            $content = $question->question_text;
            preg_match_all('/storage\/(questions\/content\/[a-zA-Z0-9\._-]+)/', $content, $matches);

            if (isset($matches[1])) {
                foreach ($matches[1] as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }

            $question->attachments()->delete();
            $question->options()->delete();
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questionCategory(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function attachments()
    {
        return $this->morphMany(QuestionAttachment::class, 'attachable');
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'question_id');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot('order');
    }
}
