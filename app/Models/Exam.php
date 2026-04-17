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
            // Hapus relasi pivot (classroom)
            $exam->classrooms()->detach();

            // Hapus token & pertanyaan ujian
            $exam->tokens()->delete();
            $exam->questions()->delete();

            // Hapus sesi ujian siswa
            // Karena ExamSession punya child (answers & questionOrders),
            // kita loop agar event deleting di model ExamSession juga terpicu (jika ada logic tambahan disana)
            $exam->sessions->each(function ($session) {
                // Jawaban (exam_answers) dan Urutan (exam_question_orders)
                // akan terhapus otomatis via Database Cascade dari Session
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
    // app/Models/Exam.php

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
