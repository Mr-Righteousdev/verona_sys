<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Competency;
use App\Models\Score;
use App\Models\Student;

/**
 * Rule + trend based competency gap detection.
 *
 * For each competency a student has been scored on, look at the most recent
 * WINDOW scores (CBC levels 1-3) and flag the competency when:
 *  - the recent average is below the competency benchmark,
 *  - the scores are clearly declining (least-squares slope), or
 *  - the trend projects the next score below the benchmark (early warning).
 * Low attendance adds weight to an existing flag but never raises one alone.
 */
class GapDetector
{
    public const WINDOW = 5;

    public const MIN_SCORES = 2;

    public const MIN_TREND_SCORES = 4;  // fewer points on a 1-3 scale make one dip look like a collapse

    public const DECLINE_SLOPE = -0.3;   // levels lost per assessment

    public const LOW_ATTENDANCE = 0.75;  // share of days present, last 30 days

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
     * @return array{average: float, slope: ?float, projected: ?float, reasons: list<string>}
     */
    public function analyse(array $levels, float $benchmark): array
    {
        $n = count($levels);
        $average = array_sum($levels) / $n;
        $slope = $projected = null;

        if ($n >= self::MIN_TREND_SCORES) {
            $meanX = ($n - 1) / 2;
            $num = $den = 0.0;
            foreach ($levels as $x => $y) {
                $num += ($x - $meanX) * ($y - $average);
                $den += ($x - $meanX) ** 2;
            }
            $slope = $num / $den;
            $projected = max(1.0, min(3.0, $average + $slope * ($n - $meanX)));
        }

        $reasons = [];
        if ($average < $benchmark) {
            $reasons[] = sprintf('Recent average %.2f is below the benchmark of %.2f.', $average, $benchmark);
        }
        if ($slope !== null && $slope <= self::DECLINE_SLOPE) {
            $reasons[] = sprintf('Scores are declining by %.2f levels per assessment.', abs($slope));
        }
        if ($projected !== null && $average >= $benchmark && $projected < $benchmark) {
            $reasons[] = sprintf('Trend projects the next score at %.2f, below the benchmark.', $projected);
        }

        return compact('average', 'slope', 'projected', 'reasons');
    }

    public function severity(array $reasons, float $average): string
    {
        if (count($reasons) >= 3 || $average < 1.5) {
            return 'high';
        }

        return count($reasons) === 2 ? 'medium' : 'low';
    }

    private function evaluate(Student $student, Competency $competency, ?float $attendance): void
    {
        $levels = Score::query()
            ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->where('scores.student_id', $student->id)
            ->where('scores.competency_id', $competency->id)
            ->orderByDesc('assessments.assessed_on')
            ->orderByDesc('scores.id')
            ->limit(self::WINDOW)
            ->pluck('scores.level')
            ->reverse()
            ->values()
            ->all();

        if (count($levels) < self::MIN_SCORES) {
            return;
        }

        $result = $this->analyse($levels, $competency->benchmark);
        $reasons = $result['reasons'];

        if ($reasons && $attendance !== null && $attendance < self::LOW_ATTENDANCE) {
            $reasons[] = sprintf('Attendance is %d%% over the last 30 days.', round($attendance * 100));
        }

        $open = Alert::open()
            ->where('student_id', $student->id)
            ->where('competency_id', $competency->id)
            ->first();

        if (! $reasons) {
            $open?->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution_note' => 'Auto-cleared: competency back on track.',
            ]);

            return;
        }

        $data = [
            'severity' => $this->severity($reasons, $result['average']),
            'reasons' => $reasons,
            'recent_average' => round($result['average'], 2),
            'projected_level' => $result['projected'] === null ? null : round($result['projected'], 2),
            'recommendation' => $competency->intervention,
        ];

        $open
            ? $open->update($data)
            : Alert::create($data + ['student_id' => $student->id, 'competency_id' => $competency->id]);
    }
}
