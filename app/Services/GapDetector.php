<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Competency;
use App\Models\Score;
use App\Models\Student;

/**
 * Rule + trend based competency gap detection, with intervention follow-up.
 *
 * For each competency a student has been scored on, look at the most recent
 * WINDOW scores (CBC scale 0.9-3.0) and flag the competency when:
 *  - the recent average is below the competency benchmark,
 *  - the scores are clearly declining (least-squares slope), or
 *  - the trend projects the next score below the benchmark (early warning).
 * Low attendance adds weight to an existing flag but never raises one alone.
 *
 * Once a teacher records an intervention, later runs follow it up: the alert
 * resolves as "improved" when the learner is back on track, and is escalated
 * when ESCALATE_AFTER further assessments show no real improvement.
 */
class GapDetector
{
    public const WINDOW = 5;

    public const MIN_SCORES = 2;

    public const MIN_TREND_SCORES = 4;   // fewer points make one dip look like a collapse

    public const DECLINE_SLOPE = -0.25;  // levels lost per assessment that count as declining

    public const TREND_LABEL = 0.1;      // slope beyond ±this reads as improving/declining

    public const LOW_ATTENDANCE = 0.75;  // share of days present, last 30 days

    public const ESCALATE_AFTER = 3;     // assessments after an intervention before escalating

    public const IMPROVEMENT = 0.2;      // rise over the baseline that counts as real progress

    public function run(Student $student): void
    {
        $attendance = $student->attendanceRate();

        foreach (Competency::all() as $competency) {
            $this->evaluate($student, $competency, $attendance);
        }
    }

    public function runAll(): int
    {
        $count = 0;
        Student::query()->each(function (Student $student) use (&$count) {
            $this->run($student);
            $count++;
        });

        return $count;
    }

    /**
     * Pure analysis of a chronological list of levels; no database access.
     *
     * @param  list<int|float>  $levels  oldest first
     * @return array{average: float, slope: ?float, projected: ?float, trend: ?string, reasons: list<string>}
     */
    public function analyse(array $levels, float $benchmark): array
    {
        $n = count($levels);
        $average = array_sum($levels) / $n;
        $slope = $projected = $trend = null;

        if ($n >= 3) {
            $meanX = ($n - 1) / 2;
            $num = $den = 0.0;
            foreach (array_values($levels) as $x => $y) {
                $num += ($x - $meanX) * ($y - $average);
                $den += ($x - $meanX) ** 2;
            }
            $slope = $num / $den;
            $trend = $slope >= self::TREND_LABEL ? 'improving' : ($slope <= -self::TREND_LABEL ? 'declining' : 'steady');
            $projected = max(Score::MIN, min(Score::MAX, $average + $slope * ($n - $meanX)));
        }

        $reasons = [];
        if ($average < $benchmark) {
            $reasons[] = sprintf('Recent average %.2f is below the benchmark of %.1f.', $average, $benchmark);
        }
        if ($n >= self::MIN_TREND_SCORES && $slope <= self::DECLINE_SLOPE) {
            $reasons[] = sprintf('Scores are declining by %.2f per assessment.', abs($slope));
        }
        if ($n >= self::MIN_TREND_SCORES && $trend === 'declining' && $average >= $benchmark && $projected < $benchmark) {
            $reasons[] = sprintf('Trend projects the next score at %.2f, below the benchmark.', $projected);
        }

        return compact('average', 'slope', 'projected', 'trend', 'reasons');
    }

    /**
     * high: well below the benchmark, or three or more reasons;
     * medium: below the benchmark, or two reasons;
     * low: an early warning from the trend alone.
     */
    public function severity(array $reasons, float $average, float $benchmark): string
    {
        return match (true) {
            count($reasons) >= 3 || $average < $benchmark - 0.3 => 'high',
            count($reasons) === 2 || $average < $benchmark => 'medium',
            default => 'low',
        };
    }

    /** Recent levels for one learner and competency, oldest first. */
    public function recentLevels(Student $student, Competency $competency): array
    {
        return Score::query()
            ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->where('scores.student_id', $student->id)
            ->where('scores.competency_id', $competency->id)
            ->orderByDesc('assessments.assessed_on')
            ->orderByDesc('scores.id')
            ->limit(self::WINDOW)
            ->pluck('scores.level')
            ->map(fn ($l) => (float) $l)
            ->reverse()
            ->values()
            ->all();
    }

    private function evaluate(Student $student, Competency $competency, ?float $attendance): void
    {
        $levels = $this->recentLevels($student, $competency);

        if (count($levels) < self::MIN_SCORES) {
            return;
        }

        $result = $this->analyse($levels, $competency->benchmark);
        $reasons = $result['reasons'];

        if ($reasons && $attendance !== null && $attendance < self::LOW_ATTENDANCE) {
            $reasons[] = sprintf('Attendance is %d%% over the last 30 days.', round($attendance * 100));
        }

        $open = Alert::open()
            ->with('latestIntervention')
            ->where('student_id', $student->id)
            ->where('competency_id', $competency->id)
            ->first();

        if (! $reasons) {
            $open?->update([
                'status' => 'resolved',
                'outcome' => $open->status === 'in_progress' ? 'improved' : 'cleared',
                'resolved_at' => now(),
                'resolution_note' => $open->status === 'in_progress'
                    ? 'Improved after intervention: back on track.'
                    : 'Back on track without intervention.',
            ]);

            return;
        }

        $data = [
            'severity' => $this->severity($reasons, $result['average'], $competency->benchmark),
            'reasons' => $reasons,
            'recent_average' => round($result['average'], 2),
            'projected_level' => $result['projected'] === null ? null : round($result['projected'], 2),
            'recommendation' => $competency->intervention,
        ];

        if ($open?->status === 'in_progress' && $intervention = $open->latestIntervention) {
            $progress = $intervention->progress();
            if ($progress['count'] >= self::ESCALATE_AFTER && $progress['change'] < self::IMPROVEMENT) {
                $data['reasons'][] = sprintf(
                    'No real improvement after %d assessments since the intervention on %s (%.2f → %.2f).',
                    $progress['count'], $intervention->started_on->format('j M'), $intervention->baseline_average, $progress['average'],
                );
                $data['escalated_at'] = $open->escalated_at ?? now();
                $data['severity'] = 'high';
            }
        }

        $open
            ? $open->update($data)
            : Alert::create($data + ['student_id' => $student->id, 'competency_id' => $competency->id]);
    }
}
