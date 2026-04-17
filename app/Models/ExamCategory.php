<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExamCategory extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $slug = Str::slug($category->name);

            // Cek apakah slug sudah ada
            $count = static::where('slug', 'like', "{$slug}%")->count();

            // Jika ada yang sama, tambah suffix angka
            $category->slug = $count ? "{$slug}-{$count}" : $slug;
        });

        // Tambahkan juga saat updating jika nama berubah
        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $slug = Str::slug($category->name);
                $count = static::where('slug', 'like', "{$slug}%")
                    ->where('id', '!=', $category->id)
                    ->count();
                $category->slug = $count ? "{$slug}-{$count}" : $slug;
            }
        });
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
