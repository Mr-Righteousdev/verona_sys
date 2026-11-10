<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['student_id', 'competency_id', 'severity', 'reasons', 'recent_average', 'projected_level', 'recommendation', 'status', 'outcome', 'escalated_at', 'resolved_by', 'resolved_at', 'resolution_note'])]
class Alert extends Model
{
    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'recent_average' => 'float',
            'projected_level' => 'float',
            'resolved_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    /** Unresolved: newly flagged, or with an intervention under way. */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ['open', 'in_progress']);
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

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class)->latest('started_on')->latest('id');
    }

    public function latestIntervention(): HasOne
    {
        return $this->hasOne(Intervention::class)->latestOfMany('started_on');
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->status === 'resolved' => match ($this->outcome) {
                'improved' => 'Improved',
                'cleared' => 'Back on track',
                default => 'Closed',
            },
            $this->escalated_at !== null => 'Escalated',
            $this->status === 'in_progress' => 'Intervention under way',
            default => 'Needs action',
        };
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
