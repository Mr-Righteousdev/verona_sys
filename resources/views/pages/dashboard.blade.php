<?php

use App\Models\Alert;
use App\Models\Classroom;
use App\Models\Competency;
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
        $open = Alert::open()->inClass($this->classroomId);
        $classroom = $this->classroomId ? Classroom::find($this->classroomId) : null;

        $byCompetency = Competency::all()->map(fn ($c) => [
            'competency' => $c,
            'count' => (clone $open)->where('competency_id', $c->id)->count(),
        ])->sortByDesc('count')->values();

        $byClass = Classroom::orderBy('name')->get()->map(fn ($c) => [
            'classroom' => $c,
            'count' => Alert::open()->inClass($c->id)->count(),
        ]);

        return [
            'classroom' => $classroom,
            'term' => \App\Models\Assessment::latest('assessed_on')->value('term'),
            'classrooms' => Classroom::orderBy('name')->get(),
            'learnerCount' => (clone $open)->distinct('student_id')->count('student_id'),
            'openCount' => (clone $open)->count(),
            // One board per learner: the most urgent gap for each of the three most urgent learners.
            'urgent' => (clone $open)->with('student.classroom', 'competency')->urgentFirst()->get()->unique('student_id')->take(3),
            'resolvedThisWeek' => Alert::where('status', 'resolved')->inClass($this->classroomId)->where('resolved_at', '>=', now()->startOfWeek())->count(),
            'byCompetency' => $byCompetency,
            'maxCompetency' => max(1, $byCompetency->max('count')),
            'byClass' => $byClass,
            'maxClass' => max(1, $byClass->max('count')),
        ];
    }
};
?>

<div>
    <x-page-header
        :title="$learnerCount === 0 ? 'Every learner is on track' : $learnerCount.' '.str('learner')->plural($learnerCount).' need you'"
        :subtitle="($classroom?->name ?? 'Whole school').($term ? ' · Term '.$term : '').' · '.$openCount.' open '.str('alert')->plural($openCount).' · checked daily at 18:00'">
        <x-slot:actions>
            <select wire:model.live="classroomId" class="select select-bordered border-ink w-40" aria-label="Class">
                <option value="">Whole school</option>
                @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <x-button label="Enter scores" icon="o-pencil-square" link="/assessments" class="btn-secondary border-2 border-ink" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-7 xl:grid-cols-[1fr_19rem]">
        <section aria-label="Most urgent">
            @if ($urgent->isEmpty())
                <div class="board p-8">
                    <p class="display text-3xl">No open alerts</p>
                    <p class="text-muted mt-2 max-w-[60ch]">Nobody in {{ $classroom?->name ?? 'the school' }} is below a competency benchmark or trending towards it. New scores are checked every evening.</p>
                </div>
            @else
                <div class="grid gap-x-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($urgent as $alert)
                        <x-signboard :alert="$alert" class="board-enter" wire:key="a{{ $alert->id }}" />
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink mt-1 pt-3">
                    <p>{{ max(0, $openCount - $urgent->count()) }} more open in {{ $classroom?->name ?? 'the school' }} · {{ $resolvedThisWeek }} resolved this week</p>
                    <a href="/alerts{{ $classroomId ? '?class='.$classroomId : '' }}" wire:navigate class="font-bold text-primary underline">See all alerts</a>
                </div>
            @endif
        </section>

        <aside class="flex flex-col gap-6">
            <section class="board">
                <h2 class="display text-xl px-4 py-3 border-b border-ink">Open alerts by competency</h2>
                <ul>
                    @foreach ($byCompetency as $row)
                        <li>
                            <a href="/alerts?competency={{ $row['competency']->id }}{{ $classroomId ? '&class='.$classroomId : '' }}" wire:navigate
                               class="grid grid-cols-[1fr_5rem_2rem] items-center gap-3 px-4 py-2.5 text-sm border-b border-rule hover:bg-base-200">
                                <x-competency :competency="$row['competency']" short />
                                <span class="h-2.5 bg-base-200"><span class="block h-full" style="width: {{ $row['count'] / $maxCompetency * 100 }}%; background: {{ $row['competency']->colour }}"></span></span>
                                <span class="text-right font-bold tabular">{{ $row['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
            <section class="board">
                <h2 class="display text-xl px-4 py-3 border-b border-ink">By class</h2>
                <ul>
                    @foreach ($byClass as $row)
                        <li>
                            <button wire:click="$set('classroomId', {{ $row['classroom']->id }})" @class(['w-full grid grid-cols-[4.5rem_1fr_2rem] items-center gap-3 px-4 py-2.5 text-sm border-b border-rule hover:bg-base-200 cursor-pointer text-left', 'font-bold bg-base-200' => $classroomId === $row['classroom']->id])>
                                <span>{{ $row['classroom']->name }}</span>
                                <span class="h-2.5 bg-base-200"><span class="block h-full bg-primary" style="width: {{ $row['count'] / $maxClass * 100 }}%"></span></span>
                                <span class="text-right font-bold tabular">{{ $row['count'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
</div>
