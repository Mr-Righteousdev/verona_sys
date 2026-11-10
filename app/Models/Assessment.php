<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['classroom_id', 'subject_id', 'teacher_id', 'title', 'type', 'term', 'assessed_on'])]
class Assessment extends Model
{
    public const TYPES = ['assignment', 'test', 'project', 'observation'];

    protected function casts(): array
    {
        return ['assessed_on' => 'date'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(Competency::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
