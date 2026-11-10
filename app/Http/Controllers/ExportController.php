<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Score;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV downloads that open directly in Excel. */
class ExportController extends Controller
{
    public function scores(Request $request): StreamedResponse
    {
        $rows = Score::query()
            ->with('assessment.subject', 'assessment.classroom', 'student', 'competency')
            ->when($request->integer('class'), fn ($q, $id) => $q->whereHas('student', fn ($s) => $s->where('classroom_id', $id)))
            ->get()
            ->sortBy(fn ($s) => [$s->assessment->assessed_on, $s->student->name])
            ->map(fn ($s) => [
                $s->assessment->assessed_on->toDateString(), $s->assessment->classroom->name, $s->student->admission_no, $s->student->name,
                $s->assessment->subject->name, $s->assessment->title, $s->competency->name, number_format($s->level, 1), Score::band($s->level),
            ]);

        return $this->csv('scores', ['Date', 'Class', 'Admission no', 'Learner', 'Subject', 'Assessment', 'Competency', 'Score', 'Band'], $rows);
    }

    public function alerts(Request $request): StreamedResponse
    {
        $rows = Alert::query()
            ->with('student.classroom', 'competency', 'latestIntervention')
            ->inClass($request->integer('class') ?: null)
            ->urgentFirst()
            ->get()
            ->map(function ($a) {
                $p = $a->latestIntervention?->progress();

                return [
                    $a->student->classroom->name, $a->student->admission_no, $a->student->name, $a->competency->name,
                    $a->severity, $a->statusLabel(), number_format($a->recent_average, 2), implode(' ', $a->reasons),
                    $a->latestIntervention?->started_on->toDateString(), $a->latestIntervention?->action,
                    $a->latestIntervention ? number_format($a->latestIntervention->baseline_average, 2) : null,
                    $p && $p['average'] !== null ? number_format($p['average'], 2) : null,
                    $a->created_at->toDateString(), $a->resolved_at?->toDateString(),
                ];
            });

        return $this->csv('alerts', ['Class', 'Admission no', 'Learner', 'Competency', 'Severity', 'Status', 'Recent average', 'Reasons',
            'Intervention started', 'Intervention', 'Average before', 'Average since', 'Raised', 'Resolved'], $rows);
    }

    private function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads names correctly
            fputcsv($out, $header);
            foreach ($rows as $row) {
                // Stop Excel treating typed text such as "=SUM(...)" as a formula (CSV injection).
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, "verona-$name-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
