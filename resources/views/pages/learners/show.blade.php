<?php

use App\Models\Alert;
use App\Models\Competency;
use App\Models\Student;
use Livewire\Component;

new class extends Component {
    public Student $student;

    public function with(): array
    {
        $this->student->load('classroom');

        return [
            'competencies' => Competency::all(),
            'summary' => $this->student->competencySummary(),
            'rate' => $this->student->attendanceRate(),
            'openAlerts' => $this->student->alerts()->open()->with('student.classroom', 'competency')->urgentFirst()->get(),
            'history' => $this->student->alerts()->where('status', 'resolved')->with('competency', 'latestIntervention')->latest('resolved_at')->get(),
        ];
    }

    public function rendering($view): void
    {
        $view->title($this->student->name);
    }
};
?>

<div>
    <a href="/learners" wire:navigate class="inline-flex items-center gap-1 text-sm text-navy font-bold mb-4 no-print"><x-icon name="o-arrow-left" class="size-4" /> All learners</a>

    <x-page-header :title="$student->name" :subtitle="$student->classroom->name.' · '.$student->admission_no">
        <x-slot:actions>
            <x-button label="Progress report" icon="o-printer" link="{{ route('reports.learner', $student) }}" class="btn-outline" />
        </x-slot:actions>
    </x-page-header>

    @php($trends = collect($summary)->countBy('trend'))
    <section class="grid gap-3 grid-cols-2 md:grid-cols-4 mb-7" aria-label="Summary">
        <x-kpi label="Attendance" :value="$rate === null ? '—' : round($rate * 100).'%'" note="Last 30 days" />
        <x-kpi label="Open alerts" :value="$openAlerts->count()" :note="$openAlerts->where('status', 'in_progress')->count().' with an intervention'" />
        <x-kpi label="Improving" :value="$trends['improving'] ?? 0" note="Competencies trending up" />
        <x-kpi label="Declining" :value="$trends['declining'] ?? 0" note="Competencies trending down" />
    </section>

    @if ($openAlerts->isNotEmpty())
        <section class="mb-8" aria-labelledby="open-h">
            <h2 id="open-h" class="heading text-lg mb-3">Needs attention</h2>
            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($openAlerts as $alert)<x-alert-card :alert="$alert" />@endforeach
            </div>
        </section>
    @endif

    <section class="mb-8" aria-labelledby="all-h">
        <h2 id="all-h" class="heading text-lg mb-3">All seven competencies</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($competencies as $c)
                @php($s = $summary[$c->id] ?? null)
                <article class="panel p-4">
                    <div class="flex items-start justify-between gap-3">
                        <x-competency :competency="$c" class="font-bold leading-snug" />
                        @if ($s)
                            <p class="text-right shrink-0"><span class="text-xl font-bold tabular">{{ number_format($s['average'], 2) }}</span><span class="block text-xs text-muted">{{ \App\Models\Score::band($s['average']) }}</span></p>
                        @endif
                    </div>
                    @if ($s)
                        <x-trend :levels="$s['levels']" :benchmark="$c->benchmark" :colour="$c->colour" class="mt-3" />
                        <div class="flex items-center justify-between gap-2 mt-2">
                            <span class="text-xs text-muted">{{ count($s['levels']) }} {{ str('score')->plural(count($s['levels'])) }} this term</span>
                            <x-trend-label :trend="$s['trend']" />
                        </div>
                    @else
                        <p class="text-sm text-muted mt-3">Not assessed yet this term.</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    @if ($history->isNotEmpty())
        <section class="panel" aria-labelledby="hist-h">
            <div class="panel-head"><h2 id="hist-h" class="heading text-base">Resolved alerts this term</h2></div>
            <table class="table text-sm">
                <thead><tr><th>Competency</th><th>Outcome</th><th class="hidden md:table-cell">Note</th><th class="text-right">Resolved</th></tr></thead>
                <tbody>
                    @foreach ($history as $a)
                        <tr>
                            <td><a href="{{ route('alerts.show', $a) }}" wire:navigate class="hover:underline"><x-competency :competency="$a->competency" /></a></td>
                            <td><x-alert-status :alert="$a" /></td>
                            <td class="hidden md:table-cell text-muted">{{ $a->resolution_note }}</td>
                            <td class="text-right whitespace-nowrap">{{ $a->resolved_at?->format('j M') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</div>
