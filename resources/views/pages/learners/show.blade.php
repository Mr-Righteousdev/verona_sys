<?php

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
            'levels' => $this->student->levelsByCompetency(),
            'rate' => $this->student->attendanceRate(),
            'openAlerts' => $this->student->alerts()->open()->with('student.classroom', 'competency')->urgentFirst()->get(),
        ];
    }

    public function rendering($view): void
    {
        $view->title($this->student->name);
    }
};
?>

<div>
    <a href="/learners" wire:navigate class="inline-flex items-center gap-1 text-primary font-bold mb-4"><x-icon name="o-arrow-left" class="size-4" /> All learners</a>

    <x-page-header :title="$student->name"
        :subtitle="$student->classroom->name.' · '.$student->admission_no.' · attendance '.($rate === null ? 'not recorded' : round($rate * 100).'% over 30 days')" />

    @if ($openAlerts->isNotEmpty())
        <section class="mb-8" aria-label="Open alerts">
            <h2 class="display text-2xl mb-3">Needs attention</h2>
            <div class="grid gap-x-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($openAlerts as $alert)<x-signboard :alert="$alert" />@endforeach
            </div>
        </section>
    @endif

    <section aria-label="Competencies">
        <h2 class="display text-2xl mb-3">All seven competencies</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($competencies as $c)
                @php($l = $levels[$c->id] ?? [])
                @php($recent = array_slice($l, -5))
                <article class="board">
                    <div class="h-2" style="background: {{ $c->colour }}"></div>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold leading-snug">{{ $c->name }}</h3>
                            @if ($l)
                                <p class="text-right shrink-0"><span class="display text-3xl">{{ number_format(array_sum($recent) / count($recent), 1) }}</span><span class="block text-xs text-muted">recent avg</span></p>
                            @endif
                        </div>
                        @if ($l)
                            <x-trend :levels="$l" :benchmark="$c->benchmark" :colour="$c->colour" class="mt-3" />
                            <p class="text-xs text-muted mt-1">{{ count($l) }} {{ str('score')->plural(count($l)) }} this term · latest {{ \App\Models\Score::LEVELS[end($l)] }}</p>
                        @else
                            <p class="text-sm text-muted mt-3">Not assessed yet this term.</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
