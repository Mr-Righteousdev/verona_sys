<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'competency_id', 'severity', 'reasons', 'recent_average', 'projected_level', 'recommendation', 'status', 'resolved_by', 'resolved_at', 'resolution_note'])]
class Alert extends Model
{
    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'recent_average' => 'float',
            'projected_level' => 'float',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open');
    }

    public function scopeUrgentFirst(Builder $query): void
    {
        $query->orderByRaw("case severity when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderBy('recent_average');
    }

    public function scopeInClass(Builder $query, ?int $classroomId): void
    {
        $query->when($classroomId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('classroom_id', $classroomId)));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
