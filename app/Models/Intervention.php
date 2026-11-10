<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['alert_id', 'user_id', 'action', 'started_on', 'baseline_average'])]
class Intervention extends Model
{
    protected function casts(): array
    {
        return ['started_on' => 'date', 'baseline_average' => 'float'];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scores for this learner and competency on or after the intervention began.
     *
     * @return list<float> oldest first
     */
    public function levelsSince(): array
    {
        return Score::query()
            ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->where('scores.student_id', $this->alert->student_id)
            ->where('scores.competency_id', $this->alert->competency_id)
            ->where('assessments.assessed_on', '>=', $this->started_on->toDateString())
            ->orderBy('assessments.assessed_on')
            ->pluck('scores.level')
            ->map(fn ($l) => (float) $l)
            ->all();
    }

    /** @return array{count: int, average: ?float, change: ?float} */
    public function progress(): array
    {
        $levels = $this->levelsSince();
        $average = $levels ? array_sum($levels) / count($levels) : null;

        return [
            'count' => count($levels),
            'average' => $average,
            'change' => $average === null ? null : $average - $this->baseline_average,
        ];
    }
}
