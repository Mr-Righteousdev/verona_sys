<?php

use App\Models\Alert;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Score;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Reports')] class extends Component {
    #[Url(as: 'class')]
    public ?int $classroomId = null;

    public function mount(): void
    {
        $this->classroomId ??= Classroom::where('class_teacher_id', auth()->id())->value('id') ?? Classroom::orderBy('name')->value('id');
    }

    public function with(): array
    {
        $competencies = Competency::all();
        $students = Student::where('classroom_id', $this->classroomId)->orderBy('name')
            ->withCount(['alerts as open_alerts_count' => fn ($q) => $q->open()])
            ->get();

        $averages = Score::whereIn('student_id', $students->pluck('id'))
            ->groupBy('student_id', 'competency_id')
            ->selectRaw('student_id, competency_id, avg(level) as average')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('average', 'competency_id'));

        // Did interventions work? One row per competency, whole school.
        $effectiveness = $competencies->map(function ($c) {
            $alerts = Alert::where('competency_id', $c->id)->has('interventions');

            return [
                'competency' => $c,
                'total' => (clone $alerts)->count(),
                'improved' => (clone $alerts)->where('outcome', 'improved')->count(),
                'underway' => (clone $alerts)->where('status', 'in_progress')->whereNull('escalated_at')->count(),
                'escalated' => (clone $alerts)->open()->whereNotNull('escalated_at')->count(),
            ];
        });

        return [
            'classrooms' => Classroom::orderBy('name')->get(),
            'competencies' => $competencies,
            'students' => $students,
            'averages' => $averages,
            'effectiveness' => $effectiveness,
            'totals' => [
                'total' => $effectiveness->sum('total'),
                'improved' => $effectiveness->sum('improved'),
                'underway' => $effectiveness->sum('underway'),
                'escalated' => $effectiveness->sum('escalated'),
            ],
        ];
    }
};
?>

<div>
    <x-page-header title="Reports" subtitle="Class summaries, intervention results, printable learner reports and Excel exports.">
        <x-slot:actions>
            <select wire:model.live="classroomId" class="select w-40" aria-label="Class">
                @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <x-button label="Scores (Excel)" icon="o-arrow-down-tray" link="{{ route('export.scores', ['class' => $classroomId]) }}" no-wire-navigate external class="btn-outline" />
            <x-button label="Alerts (Excel)" icon="o-arrow-down-tray" link="{{ route('export.alerts', ['class' => $classroomId]) }}" no-wire-navigate external class="btn-outline" />
        </x-slot:actions>
    </x-page-header>

    <section class="panel mb-7" aria-labelledby="class-h">
        <div class="panel-head">
            <h2 id="class-h" class="heading text-base">{{ $classrooms->firstWhere('id', $classroomId)?->name }}: average score per learner</h2>
            <span class="text-xs text-muted hidden sm:inline">Highlighted: below the benchmark · open a learner for their printable report</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 bg-base-100">Learner</th>
                        @foreach ($competencies as $c)<th class="text-center" title="{{ $c->name }}"><x-competency :competency="$c" short class="justify-center" /></th>@endforeach
                        <th class="text-right">Open alerts</th>
                        <th class="no-print"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $s)
                        <tr wire:key="rs{{ $s->id }}">
                            <td class="sticky left-0 bg-base-100 font-bold whitespace-nowrap"><a href="{{ route('learners.show', $s) }}" wire:navigate class="hover:underline">{{ $s->name }}</a></td>
                            @foreach ($competencies as $c)
                                @php($avg = $averages[$s->id][$c->id] ?? null)
                                <td class="text-center tabular">
                                    @if ($avg === null)<span class="text-muted">—</span>
                                    @elseif ($avg < $c->benchmark)<span class="tag tag-medium">{{ number_format($avg, 1) }}</span>
                                    @else{{ number_format($avg, 1) }}@endif
                                </td>
                            @endforeach
                            <td class="text-right">{{ $s->open_alerts_count ?: '—' }}</td>
                            <td class="text-right no-print"><a href="{{ route('reports.learner', $s) }}" wire:navigate class="text-navy font-bold hover:underline whitespace-nowrap">Report</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel" aria-labelledby="eff-h">
        <div class="panel-head">
            <h2 id="eff-h" class="heading text-base">Do interventions work? Whole school, this term</h2>
            @if ($totals['total'])
                <span class="tag tag-good">{{ round($totals['improved'] / $totals['total'] * 100) }}% improved</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="table text-sm">
                <thead><tr><th>Competency</th><th class="text-right">Interventions</th><th class="text-right">Improved</th><th class="text-right">Under way</th><th class="text-right">Escalated</th><th class="text-right">Success rate</th></tr></thead>
                <tbody>
                    @foreach ($effectiveness as $row)
                        <tr>
                            <td><x-competency :competency="$row['competency']" /></td>
                            <td class="text-right tabular">{{ $row['total'] }}</td>
                            <td class="text-right tabular">{{ $row['improved'] }}</td>
                            <td class="text-right tabular">{{ $row['underway'] }}</td>
                            <td class="text-right tabular">{{ $row['escalated'] }}</td>
                            <td class="text-right tabular font-bold">{{ $row['total'] ? round($row['improved'] / $row['total'] * 100).'%' : '—' }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold">
                        <td>All competencies</td>
                        <td class="text-right tabular">{{ $totals['total'] }}</td>
                        <td class="text-right tabular">{{ $totals['improved'] }}</td>
                        <td class="text-right tabular">{{ $totals['underway'] }}</td>
                        <td class="text-right tabular">{{ $totals['escalated'] }}</td>
                        <td class="text-right tabular">{{ $totals['total'] ? round($totals['improved'] / $totals['total'] * 100).'%' : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="px-5 py-3 text-xs text-muted border-t border-rule">Success rate = alerts resolved as improved ÷ alerts that had an intervention. Interventions still under way count against the rate until they finish.</p>
    </section>
</div>
