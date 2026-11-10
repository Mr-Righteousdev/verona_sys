<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'classroom_id', 'name', 'admission_no', 'gender'])]
class Student extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /** Levels per competency id, oldest first. */
    public function levelsByCompetency(): array
    {
        return $this->scores()
            ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->orderBy('assessments.assessed_on')
            ->get(['scores.competency_id', 'scores.level'])
            ->groupBy('competency_id')
            ->map(fn ($rows) => $rows->pluck('level')->map(fn ($l) => (float) $l)->all())
            ->all();
    }

    /**
     * Per competency id: all levels this term, recent average and trend label.
     *
     * @return array<int, array{levels: list<float>, average: ?float, trend: ?string}>
     */
    public function competencySummary(): array
    {
        $detector = app(\App\Services\GapDetector::class);
        $benchmarks = Competency::pluck('benchmark', 'id');

        return collect($this->levelsByCompetency())->map(function ($levels, $id) use ($detector, $benchmarks) {
            $recent = array_slice($levels, -\App\Services\GapDetector::WINDOW);
            $analysis = $detector->analyse($recent, (float) $benchmarks[$id]);

            return ['levels' => $levels, 'average' => $analysis['average'], 'trend' => $analysis['trend']];
        })->all();
    }

    /** Share of days present over the last 30 days, or null without records. */
    public function attendanceRate(): ?float
    {
        $recent = $this->attendances()->where('date', '>=', now()->subDays(30));
        $total = (clone $recent)->count();

        return $total ? $recent->where('present', true)->count() / $total : null;
    }
}
