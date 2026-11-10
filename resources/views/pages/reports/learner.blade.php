<?php

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Student;
use Livewire\Component;

new class extends Component {
    public Student $student;

    public function with(): array
    {
        $this->student->load('classroom.classTeacher');

        return [
            'competencies' => Competency::all(),
            'summary' => $this->student->competencySummary(),
            'rate' => $this->student->attendanceRate(),
            'term' => Assessment::latest('assessed_on')->value('term'),
            'alerts' => $this->student->alerts()->with('competency', 'interventions.teacher')->latest()->get(),
        ];
    }

    public function rendering($view): void
    {
        $view->title('Progress report · '.$this->student->name);
    }
};
?>

<div class="max-w-4xl">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5 no-print">
        <a href="{{ route('learners.show', $student) }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-navy font-bold"><x-icon name="o-arrow-left" class="size-4" /> {{ $student->name }}</a>
        <button onclick="window.print()" class="btn btn-primary"><x-icon name="o-printer" class="size-5" /> Print or save as PDF</button>
    </div>

    <article class="panel p-6 md:p-10">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-rule pb-5">
            <x-app-brand />
            <div class="text-right">
                <h1 class="heading text-xl">Competency progress report</h1>
                <p class="text-sm text-muted">Term {{ $term }} · {{ now()->format('j F Y') }}</p>
            </div>
        </header>

        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 py-5 border-b border-rule text-sm">
            <div><dt class="text-muted">Learner</dt><dd class="font-bold">{{ $student->name }}</dd></div>
            <div><dt class="text-muted">Admission no.</dt><dd class="font-bold">{{ $student->admission_no }}</dd></div>
            <div><dt class="text-muted">Class</dt><dd class="font-bold">{{ $student->classroom->name }}</dd></div>
            <div><dt class="text-muted">Attendance (30 days)</dt><dd class="font-bold">{{ $rate === null ? 'Not recorded' : round($rate * 100).'%' }}</dd></div>
        </dl>

        <h2 class="heading text-base mt-6 mb-2">Competencies</h2>
        <table class="table text-sm">
            <thead><tr><th>Competency</th><th class="text-right">Scores</th><th class="text-right">Average</th><th>Band</th><th>Trend</th></tr></thead>
            <tbody>
                @foreach ($competencies as $c)
                    @php($s = $summary[$c->id] ?? null)
                    <tr>
                        <td><x-competency :competency="$c" /></td>
                        <td class="text-right tabular">{{ $s ? count($s['levels']) : 0 }}</td>
                        <td class="text-right tabular font-bold">{{ $s ? number_format($s['average'], 2) : '—' }}</td>
                        <td>{{ $s ? \App\Models\Score::band($s['average']) : 'Not assessed' }}</td>
                        <td>@if ($s)<x-trend-label :trend="$s['trend']" />@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-muted mt-2">Scale 0.9–3.0: below 1.5 Basic, 1.5–2.4 Moderate, 2.5 and above Outstanding. Average of the five most recent scores.</p>

        <h2 class="heading text-base mt-8 mb-2">Support this term</h2>
        @forelse ($alerts as $a)
            <div class="py-3 border-b border-rule last:border-0 text-sm">
                <div class="flex flex-wrap items-center gap-2"><x-competency :competency="$a->competency" class="font-bold" /><x-alert-status :alert="$a" /></div>
                @foreach ($a->interventions as $i)
                    <p class="mt-1">{{ $i->started_on->format('j M') }}: {{ $i->action }} <span class="text-muted">({{ $i->teacher->name }})</span></p>
                @endforeach
                @if ($a->interventions->isEmpty())<p class="mt-1 text-muted">Flagged {{ $a->created_at->format('j M') }}; no intervention recorded yet.</p>@endif
            </div>
        @empty
            <p class="text-sm text-muted">No competency gaps were flagged this term.</p>
        @endforelse

        <div class="grid md:grid-cols-2 gap-8 mt-10 text-sm">
            <div><p class="text-muted">Class teacher's comment</p><div class="border-b border-rule h-16"></div><p class="mt-2">{{ $student->classroom->classTeacher?->name }}</p></div>
            <div><p class="text-muted">Head teacher's signature</p><div class="border-b border-rule h-16"></div></div>
        </div>
    </article>
</div>
