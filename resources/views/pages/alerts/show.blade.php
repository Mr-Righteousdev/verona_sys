<?php

use App\Models\Alert;
use App\Models\Intervention;
use App\Models\Score;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Alert $alert;

    #[Validate('required|min:5|max:1000', as: 'what you did')]
    public string $action = '';

    #[Validate('required|date|before_or_equal:today', as: 'start date')]
    public string $startedOn = '';

    public string $closeNote = '';

    public function mount(): void
    {
        $this->startedOn = now()->toDateString();
    }

    public function recordIntervention(): void
    {
        $this->validate();
        Intervention::create([
            'alert_id' => $this->alert->id,
            'user_id' => auth()->id(),
            'action' => $this->action,
            'started_on' => $this->startedOn,
            'baseline_average' => $this->alert->recent_average,
        ]);
        // A new approach restarts the follow-up clock.
        $this->alert->update(['status' => 'in_progress', 'escalated_at' => null]);
        $this->reset('action');
        $this->success('Intervention recorded.', 'The next scores for this competency will show whether it is working.');
    }

    public function close(): void
    {
        $this->validate(['closeNote' => 'required|min:5|max:1000'], [], ['closeNote' => 'reason']);
        $this->alert->update([
            'status' => 'resolved', 'outcome' => 'closed', 'resolved_by' => auth()->id(),
            'resolved_at' => now(), 'resolution_note' => $this->closeNote,
        ]);
        $this->reset('closeNote');
        $this->success('Alert closed.', 'If later scores show the same gap, a new alert is raised.');
    }

    public function reopen(): void
    {
        $this->alert->update(['status' => $this->alert->interventions()->exists() ? 'in_progress' : 'open', 'outcome' => null, 'resolved_by' => null, 'resolved_at' => null, 'resolution_note' => null]);
    }

    public function with(): array
    {
        $this->alert->load('student.classroom', 'competency', 'resolver', 'interventions.teacher');

        $history = Score::query()
            ->with('assessment.subject')
            ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->where('scores.student_id', $this->alert->student_id)
            ->where('scores.competency_id', $this->alert->competency_id)
            ->orderBy('assessments.assessed_on')
            ->select('scores.*', 'assessments.assessed_on')
            ->get();

        $current = $this->alert->interventions->first();
        $marker = $current ? $history->search(fn ($s) => $s->assessment->assessed_on->gte($current->started_on)) : null;

        return [
            'history' => $history,
            'current' => $current,
            'progress' => $current?->progress(),
            'marker' => $marker === false ? null : $marker,
        ];
    }

    public function rendering($view): void
    {
        $view->title($this->alert->student->name.' · '.$this->alert->competency->name);
    }
};
?>

<div>
    <a href="/alerts" wire:navigate class="inline-flex items-center gap-1 text-sm text-navy font-bold mb-4 no-print"><x-icon name="o-arrow-left" class="size-4" /> All alerts</a>

    <x-page-header :title="$alert->student->name" :subtitle="$alert->student->classroom->name.' · '.$alert->student->admission_no">
        <x-slot:actions>
            <x-button label="Learner profile" link="{{ route('learners.show', $alert->student) }}" class="btn-outline" />
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-wrap items-center gap-2 -mt-3 mb-6">
        <x-competency :competency="$alert->competency" class="font-bold" />
        <x-severity :level="$alert->severity" />
        <x-alert-status :alert="$alert" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,26rem)_1fr] items-start">
        <div class="flex flex-col gap-6">
            <section class="panel p-5">
                <h2 class="heading text-base">Why this was flagged</h2>
                <ul class="mt-2 space-y-1.5 list-disc pl-5 text-sm">
                    @foreach ($alert->reasons as $reason)<li>{{ $reason }}</li>@endforeach
                </ul>
                <div class="mt-4 rounded-md bg-base-200 px-3 py-2.5 text-sm">
                    <p class="font-bold">Recommended action</p>
                    <p class="mt-0.5 leading-relaxed">{{ $alert->recommendation }}</p>
                </div>
            </section>

            @if ($alert->status === 'resolved')
                <section class="panel p-5">
                    <h2 class="heading text-base">{{ $alert->statusLabel() }}</h2>
                    <p class="text-sm text-muted mt-1">{{ $alert->resolver?->name ?? 'Automatically by the system' }} · {{ $alert->resolved_at?->format('j M Y') }}</p>
                    <p class="mt-2">{{ $alert->resolution_note }}</p>
                    <button wire:click="reopen" class="text-sm text-navy font-bold hover:underline mt-3 cursor-pointer">Reopen</button>
                </section>
            @else
                @if ($current)
                    <section class="panel p-5">
                        <h2 class="heading text-base">Is the intervention working?</h2>
                        <p class="text-sm text-muted mt-1">Started {{ $current->started_on->format('j M') }} by {{ $current->teacher->name }}</p>
                        <p class="mt-2 text-sm">{{ $current->action }}</p>
                        <dl class="grid grid-cols-3 gap-2 mt-4 text-center">
                            <div class="rounded-md bg-base-200 p-2"><dt class="text-xs text-muted">Before</dt><dd class="font-bold text-lg tabular">{{ number_format($current->baseline_average, 2) }}</dd></div>
                            <div class="rounded-md bg-base-200 p-2"><dt class="text-xs text-muted">Since</dt><dd class="font-bold text-lg tabular">{{ $progress['average'] === null ? '—' : number_format($progress['average'], 2) }}</dd></div>
                            <div class="rounded-md bg-base-200 p-2"><dt class="text-xs text-muted">Change</dt>
                                <dd @class(['font-bold text-lg tabular', 'text-success' => ($progress['change'] ?? 0) >= \App\Services\GapDetector::IMPROVEMENT])>{{ $progress['change'] === null ? '—' : sprintf('%+.2f', $progress['change']) }}</dd></div>
                        </dl>
                        <p class="text-sm mt-3">
                            @if ($progress['count'] === 0)
                                No scores for this competency since the intervention began. The next assessment will show progress.
                            @elseif ($alert->escalated_at)
                                <span class="font-bold">Escalated:</span> {{ $progress['count'] }} {{ str('assessment')->plural($progress['count']) }} without real improvement. Try a different approach below.
                            @else
                                {{ $progress['count'] }} {{ str('score')->plural($progress['count']) }} since. The alert closes as <em>improved</em> once the learner is back on track; it is escalated after {{ \App\Services\GapDetector::ESCALATE_AFTER }} assessments without improvement.
                            @endif
                        </p>
                    </section>
                @endif

                <form wire:submit="recordIntervention" class="panel p-5 no-print">
                    <h2 class="heading text-base">{{ $current ? 'Try a different approach' : 'Record an intervention' }}</h2>
                    <p class="text-sm text-muted mt-1 mb-3">What did you do for this learner? The system will compare later scores with today's average ({{ number_format($alert->recent_average, 2) }}).</p>
                    <x-textarea wire:model="action" placeholder="e.g. Met the learner after class and agreed a weekly check-in." rows="3" />
                    <div class="flex flex-wrap items-end gap-3 mt-3">
                        <x-input label="Started on" type="date" wire:model="startedOn" class="w-44" />
                        <x-button type="submit" label="Save intervention" spinner="recordIntervention" class="btn-primary" />
                    </div>
                </form>

                <form wire:submit="close" class="panel p-5 no-print">
                    <h2 class="heading text-base">Close without follow-up</h2>
                    <p class="text-sm text-muted mt-1 mb-3">Only if the alert does not need action, for example the scores were entered wrongly.</p>
                    <x-input wire:model="closeNote" placeholder="Reason" />
                    <x-button type="submit" label="Close alert" spinner="close" class="btn-outline mt-3" />
                </form>
            @endif
        </div>

        <div class="flex flex-col gap-6 min-w-0">
            <section class="panel p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="heading text-base">{{ $alert->competency->name }} this term</h2>
                    <p class="text-xs text-muted">Dashed: benchmark {{ number_format($alert->competency->benchmark, 1) }} · hollow points are below it @if ($marker !== null) · blue rule: intervention started @endif</p>
                </div>
                <x-trend :levels="$history->pluck('level')->all()" :benchmark="$alert->competency->benchmark" :colour="$alert->competency->colour" :marker="$marker" :width="560" :height="150" class="mt-4 max-w-2xl" />
                <table class="table mt-4 text-sm">
                    <thead><tr><th>Date</th><th>Assessment</th><th class="hidden sm:table-cell">Subject</th><th class="text-right">Score</th></tr></thead>
                    <tbody>
                        @foreach ($history->reverse() as $score)
                            <tr>
                                <td class="whitespace-nowrap">{{ $score->assessment->assessed_on->format('j M') }}</td>
                                <td>{{ $score->assessment->title }}</td>
                                <td class="hidden sm:table-cell">{{ $score->assessment->subject->name }}</td>
                                <td class="text-right whitespace-nowrap"><span class="font-bold tabular">{{ number_format($score->level, 1) }}</span> <span class="text-muted">{{ \App\Models\Score::band($score->level) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            @if ($alert->interventions->count() > 1 || ($alert->status === 'resolved' && $alert->interventions->isNotEmpty()))
                <section class="panel p-5">
                    <h2 class="heading text-base">Intervention history</h2>
                    <ol class="mt-3 space-y-3">
                        @foreach ($alert->interventions as $i)
                            <li class="text-sm border-l border-rule pl-3">
                                <p class="text-muted">{{ $i->started_on->format('j M Y') }} · {{ $i->teacher->name }} · baseline {{ number_format($i->baseline_average, 2) }}</p>
                                <p>{{ $i->action }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>
    </div>
</div>
