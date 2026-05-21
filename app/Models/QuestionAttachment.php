<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class QuestionAttachment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $appends = ['url'];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn() => rtrim(config('filesystems.disks.public.url'), '/') . '/' . ltrim($this->file_path, '/'),
        );
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
