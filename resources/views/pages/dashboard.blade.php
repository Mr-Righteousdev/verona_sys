<?php

use App\Models\Alert;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Intervention;
use App\Models\Score;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Url(as: 'class')]
    public ?int $classroomId = null;

    public function mount(): void
    {
        // Teachers land on their own class; the head teacher sees the whole school.
        $this->classroomId ??= auth()->user()->isTeacher()
            ? Classroom::where('class_teacher_id', auth()->id())->value('id')
            : null;
    }

    public function with(): array
    {
        $scope = fn ($q) => $q->inClass($this->classroomId);
        $studentIds = Student::when($this->classroomId, fn ($q) => $q->where('classroom_id', $this->classroomId))->pluck('id');
        $classrooms = Classroom::orderBy('name')->get();
        $competencies = Competency::all();

        $attendance = Attendance::whereIn('student_id', $studentIds)->where('date', '>=', now()->subDays(30)->toDateString());
        $days = (clone $attendance)->count();

        // Average score per class and competency this term.
        $averages = Score::query()
            ->join('students', 'students.id', '=', 'scores.student_id')
            ->groupBy('students.classroom_id', 'scores.competency_id')
            ->selectRaw('students.classroom_id, scores.competency_id, avg(scores.level) as average')
            ->get()
            ->groupBy('classroom_id')
            ->map(fn ($rows) => $rows->pluck('average', 'competency_id'));

        $byCompetency = $competencies->map(fn ($c) => [
            'competency' => $c,
            'count' => Alert::open()->tap($scope)->where('competency_id', $c->id)->count(),
        ])->sortByDesc('count')->values();

        $activity = collect()
            ->merge(Assessment::with('classroom', 'teacher')->when($this->classroomId, fn ($q) => $q->where('classroom_id', $this->classroomId))
                ->latest('assessed_on')->take(4)->get()
                ->map(fn ($a) => ['at' => $a->assessed_on, 'text' => "{$a->teacher->name} assessed {$a->classroom->name}: {$a->title}", 'href' => route('assessments.scores', $a)]))
            ->merge(Intervention::with('alert.student', 'alert.competency', 'teacher')->whereHas('alert', $scope)->latest('started_on')->take(4)->get()
                ->map(fn ($i) => ['at' => $i->started_on, 'text' => "{$i->teacher->name} started an intervention for {$i->alert->student->name} ({$i->alert->competency->name})", 'href' => route('alerts.show', $i->alert_id)]))
            ->merge(Alert::with('student', 'competency')->tap($scope)->where('outcome', 'improved')->latest('resolved_at')->take(4)->get()
                ->map(fn ($a) => ['at' => $a->resolved_at, 'text' => "{$a->student->name} improved in {$a->competency->name}", 'href' => route('alerts.show', $a)]))
            ->sortByDesc('at')->take(6)->values();

        return [
            'classroom' => $this->classroomId ? $classrooms->firstWhere('id', $this->classroomId) : null,
            'classrooms' => $classrooms,
            'competencies' => $competencies,
            'term' => Assessment::latest('assessed_on')->value('term'),
            'learners' => $studentIds->count(),
            'needsAction' => Alert::where('status', 'open')->tap($scope)->count(),
            'inProgress' => Alert::where('status', 'in_progress')->whereNull('escalated_at')->tap($scope)->count(),
            'escalated' => Alert::open()->whereNotNull('escalated_at')->tap($scope)->with('student.classroom', 'competency', 'latestIntervention')->get(),
            'improvedCount' => Alert::where('outcome', 'improved')->tap($scope)->count(),
            'attendanceRate' => $days ? round($attendance->where('present', true)->count() / $days * 100) : null,
            // One card per learner: the most urgent gap nobody has acted on yet.
            'urgent' => Alert::where('status', 'open')->tap($scope)->with('student.classroom', 'competency')->urgentFirst()->get()->unique('student_id')->take(3),
            'improvements' => Alert::with('student.classroom', 'competency', 'latestIntervention')->tap($scope)->where('outcome', 'improved')->latest('resolved_at')->take(5)->get(),
            'averages' => $averages,
            'byCompetency' => $byCompetency,
            'maxCompetency' => max(1, $byCompetency->max('count')),
            'activity' => $activity,
        ];
    }
};
?>

<div>
    <x-page-header title="Dashboard"
        :subtitle="($classroom?->name ?? 'Whole school').($term ? ' · Term '.$term : '').' · alerts checked daily at 18:00'">
        <x-slot:actions>
            <select wire:model.live="classroomId" class="select w-40" aria-label="Class">
                <option value="">Whole school</option>
                @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <x-button label="Enter scores" icon="o-pencil-square" link="/assessments" class="btn-primary" />
        </x-slot:actions>
    </x-page-header>

    <section class="grid gap-3 grid-cols-2 md:grid-cols-3 xl:grid-cols-6 mb-7" aria-label="Summary">
        <x-kpi label="Learners" :value="$learners" :note="$classroom?->name ?? 'All classes'" href="/learners{{ $classroomId ? '?class='.$classroomId : '' }}" />
        <x-kpi label="Need action" :value="$needsAction" note="No intervention yet" href="/alerts?status=open{{ $classroomId ? '&class='.$classroomId : '' }}" />
        <x-kpi label="Interventions under way" :value="$inProgress" note="Being followed up" href="/alerts?status=in_progress{{ $classroomId ? '&class='.$classroomId : '' }}" />
        <x-kpi label="Escalated" :value="$escalated->count()" note="No improvement yet" href="/alerts?escalated=1{{ $classroomId ? '&class='.$classroomId : '' }}" />
        <x-kpi label="Improved" :value="$improvedCount" note="After an intervention" href="/alerts?status=resolved{{ $classroomId ? '&class='.$classroomId : '' }}" />
        <x-kpi label="Attendance" :value="$attendanceRate === null ? '—' : $attendanceRate.'%'" note="Last 30 days" href="/attendance" />
    </section>

    <div class="grid gap-7 xl:grid-cols-[1fr_21rem]">
        <div class="flex flex-col gap-7 min-w-0">
            <section aria-labelledby="urgent-h">
                <div class="flex items-baseline justify-between gap-3 mb-3">
                    <h2 id="urgent-h" class="heading text-lg">Needs action</h2>
                    <a href="/alerts?status=open{{ $classroomId ? '&class='.$classroomId : '' }}" wire:navigate class="text-sm font-bold text-navy hover:underline">All {{ $needsAction }} alerts</a>
                </div>
                @if ($urgent->isEmpty())
                    <div class="panel p-6">
                        <p class="font-bold">Nothing waiting for action</p>
                        <p class="text-muted mt-1">Every flagged learner in {{ $classroom?->name ?? 'the school' }} has an intervention under way.</p>
                    </div>
                @else
                    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($urgent as $alert)
                            <x-alert-card :alert="$alert" wire:key="a{{ $alert->id }}" />
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="panel" aria-labelledby="avg-h">
                <div class="panel-head">
                    <h2 id="avg-h" class="heading text-base">Average score by class and competency</h2>
                    <p class="text-xs text-muted hidden sm:block">This term · <span class="tag tag-medium !text-xs">below 1.5</span> is the Basic band</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table text-sm">
                        <thead>
                            <tr>
                                <th>Class</th>
                                @foreach ($competencies as $c)
                                    <th class="text-center font-bold" title="{{ $c->name }}"><x-competency :competency="$c" short class="justify-center" /></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classrooms as $c)
                                <tr @class(['bg-navy-soft/60' => $classroomId === $c->id])>
                                    <td class="font-bold whitespace-nowrap"><button wire:click="$set('classroomId', {{ $c->id }})" class="hover:underline cursor-pointer">{{ $c->name }}</button></td>
                                    @foreach ($competencies as $comp)
                                        @php($avg = $averages[$c->id][$comp->id] ?? null)
                                        <td class="text-center">
                                            @if ($avg === null)<span class="text-muted">—</span>
                                            @elseif ($avg < $comp->benchmark)<span class="tag tag-medium">{{ number_format($avg, 2) }}</span>
                                            @else{{ number_format($avg, 2) }}@endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" aria-labelledby="imp-h">
                <div class="panel-head">
                    <h2 id="imp-h" class="heading text-base">Recent improvements</h2>
                    <span class="text-xs text-muted">Average before the intervention → since</span>
                </div>
                @forelse ($improvements as $a)
                    @php($p = $a->latestIntervention?->progress())
                    <a href="{{ route('alerts.show', $a) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-b border-rule last:border-0 hover:bg-base-200">
                        <span>
                            <span class="font-bold">{{ $a->student->name }}</span>
                            <span class="text-sm text-muted"> · {{ $a->student->classroom->name }}</span>
                            <x-competency :competency="$a->competency" class="block text-sm mt-0.5" />
                        </span>
                        @if ($p && $p['average'] !== null)
                            <span class="tag tag-good tabular">{{ number_format($a->latestIntervention->baseline_average, 2) }} → {{ number_format($p['average'], 2) }} (+{{ number_format($p['change'], 2) }})</span>
                        @else
                            <x-alert-status :alert="$a" />
                        @endif
                    </a>
                @empty
                    <p class="px-5 py-4 text-muted">No improvements recorded yet this term. They appear here when a learner with an intervention gets back on track.</p>
                @endforelse
            </section>
        </div>

        <aside class="flex flex-col gap-6">
            @if ($escalated->isNotEmpty())
                <section class="panel" aria-labelledby="esc-h">
                    <div class="panel-head"><h2 id="esc-h" class="heading text-base">Escalated</h2><span class="tag tag-high">{{ $escalated->count() }}</span></div>
                    <p class="px-5 pt-3 text-sm text-muted">Interventions with no real improvement after {{ \App\Services\GapDetector::ESCALATE_AFTER }} assessments. Try a different approach.</p>
                    <ul class="py-1">
                        @foreach ($escalated as $a)
                            <li><a href="{{ route('alerts.show', $a) }}" wire:navigate class="block px-5 py-2.5 hover:bg-base-200">
                                <span class="font-bold">{{ $a->student->name }}</span> <span class="text-sm text-muted">· {{ $a->student->classroom->name }}</span>
                                <x-competency :competency="$a->competency" class="block text-sm" />
                            </a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="panel" aria-labelledby="comp-h">
                <div class="panel-head"><h2 id="comp-h" class="heading text-base">Open alerts by competency</h2></div>
                <ul class="py-1">
                    @foreach ($byCompetency as $row)
                        <li>
                            <a href="/alerts?competency={{ $row['competency']->id }}{{ $classroomId ? '&class='.$classroomId : '' }}" wire:navigate
                               class="grid grid-cols-[1fr_4.5rem_1.75rem] items-center gap-3 px-5 py-2 text-sm hover:bg-base-200">
                                <x-competency :competency="$row['competency']" short />
                                <span class="h-2 rounded-full bg-base-200 overflow-hidden"><span class="block h-full rounded-full" style="width: {{ $row['count'] / $maxCompetency * 100 }}%; background: {{ $row['competency']->colour }}"></span></span>
                                <span class="text-right font-bold tabular">{{ $row['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="panel" aria-labelledby="act-h">
                <div class="panel-head"><h2 id="act-h" class="heading text-base">Recent activity</h2></div>
                <ol class="py-1">
                    @forelse ($activity as $item)
                        <li><a href="{{ $item['href'] }}" wire:navigate class="block px-5 py-2.5 text-sm hover:bg-base-200">
                            {{ $item['text'] }}
                            <span class="block text-xs text-muted mt-0.5">{{ $item['at']->format('j M') }}</span>
                        </a></li>
                    @empty
                        <li class="px-5 py-3 text-sm text-muted">No activity yet.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</div>
