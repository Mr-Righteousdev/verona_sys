<?php

use App\Models\Alert;
use App\Models\Score;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Alert $alert;

    #[Validate('required|min:5|max:1000', as: 'note')]
    public string $note = '';

    public function resolve(): void
    {
        $this->validate();
        $this->alert->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_note' => $this->note,
        ]);
        $this->reset('note');
        $this->success('Alert resolved. If later scores show the same gap, a new alert is raised.');
    }

    public function reopen(): void
    {
        $this->alert->update(['status' => 'open', 'resolved_by' => null, 'resolved_at' => null, 'resolution_note' => null]);
    }

    public function with(): array
    {
        $this->alert->load('student.classroom', 'competency', 'resolver');

        return [
            'history' => Score::query()
                ->with('assessment.subject')
                ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
                ->where('scores.student_id', $this->alert->student_id)
                ->where('scores.competency_id', $this->alert->competency_id)
                ->orderBy('assessments.assessed_on')
                ->select('scores.*')
                ->get(),
        ];
    }

    public function rendering($view): void
    {
        $view->title($this->alert->student->name.' · '.$this->alert->competency->name);
    }
};
?>

<div>
    <a href="/alerts" wire:navigate class="inline-flex items-center gap-1 text-primary font-bold mb-4"><x-icon name="o-arrow-left" class="size-4" /> All alerts</a>

    <div class="grid gap-7 lg:grid-cols-[minmax(0,26rem)_1fr]">
        <div class="flex flex-col gap-6">
            <x-signboard :alert="$alert" />

            @if ($alert->status === 'open')
                <form wire:submit="resolve" class="board p-5">
                    <h2 class="display text-xl">Mark as handled</h2>
                    <p class="text-sm text-muted mt-1 mb-3">Say what you did, so the next teacher knows.</p>
                    <x-textarea wire:model="note" placeholder="e.g. Met the learner after class and agreed a weekly check-in." rows="3" />
                    <x-button type="submit" label="Resolve alert" spinner="resolve" class="btn-secondary border-2 border-ink mt-3" />
                </form>
            @else
                <div class="board p-5">
                    <h2 class="display text-xl">Resolved</h2>
                    <p class="text-sm text-muted mt-1">{{ $alert->resolver?->name ?? 'Automatically' }} · {{ $alert->resolved_at?->format('j M Y, H:i') }}</p>
                    <p class="mt-3">{{ $alert->resolution_note }}</p>
                    <button wire:click="reopen" class="text-primary underline mt-3 cursor-pointer">Reopen</button>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-6">
            <section class="board p-5">
                <h2 class="display text-xl">Why this was flagged</h2>
                <ul class="mt-3 space-y-2 list-disc pl-5">
                    @foreach ($alert->reasons as $reason)<li>{{ $reason }}</li>@endforeach
                </ul>
            </section>

            <section class="board p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="display text-xl">{{ $alert->competency->name }} over the term</h2>
                    <p class="text-sm text-muted">Dashed line: benchmark {{ number_format($alert->competency->benchmark, 1) }} · hollow squares fall below it</p>
                </div>
                <x-trend :levels="$history->pluck('level')->all()" :benchmark="$alert->competency->benchmark" :colour="$alert->competency->colour" :width="560" :height="140" class="mt-4 max-w-2xl" />
                <table class="table mt-4">
                    <thead><tr class="border-b border-ink"><th>Date</th><th>Assessment</th><th class="hidden sm:table-cell">Subject</th><th class="text-right">Level</th></tr></thead>
                    <tbody>
                        @foreach ($history->reverse() as $score)
                            <tr class="border-b border-rule">
                                <td class="whitespace-nowrap">{{ $score->assessment->assessed_on->format('j M') }}</td>
                                <td>{{ $score->assessment->title }}</td>
                                <td class="hidden sm:table-cell">{{ $score->assessment->subject->name }}</td>
                                <td class="text-right font-bold">{{ $score->level }} <span class="font-normal text-muted">{{ \App\Models\Score::LEVELS[$score->level] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <a href="{{ route('learners.show', $alert->student) }}" wire:navigate class="inline-block mt-4 font-bold text-primary underline">Full profile for {{ $alert->student->name }}</a>
            </section>
        </div>
    </div>
</div>
