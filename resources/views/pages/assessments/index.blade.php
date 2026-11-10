<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Competency;
use App\Models\Subject;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Assessments & scores')] class extends Component {
    use WithPagination;

    #[Url(as: 'class')]
    public ?int $classroomFilter = null;

    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public string $title = '';
    public string $type = 'assignment';
    public int $term = 3;
    public string $assessed_on = '';
    public array $competency_ids = [];

    public function mount(): void
    {
        $this->assessed_on = now()->toDateString();
        $this->classroom_id = Classroom::where('class_teacher_id', auth()->id())->value('id');
    }

    public function create()
    {
        $data = $this->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|max:120',
            'type' => 'required|in:'.implode(',', Assessment::TYPES),
            'term' => 'required|integer|between:1,3',
            'assessed_on' => 'required|date|before_or_equal:today',
            'competency_ids' => 'required|array|min:1',
            'competency_ids.*' => 'exists:competencies,id',
        ], ['competency_ids.required' => 'Tick at least one competency this task assesses.']);

        $assessment = Assessment::create(collect($data)->except('competency_ids')->all() + ['teacher_id' => auth()->id()]);
        $assessment->competencies()->attach($this->competency_ids);

        return $this->redirectRoute('assessments.scores', $assessment, navigate: true);
    }

    public function with(): array
    {
        return [
            'assessments' => Assessment::with('classroom', 'subject', 'competencies', 'teacher')
                ->withCount('scores')
                ->when($this->classroomFilter, fn ($q) => $q->where('classroom_id', $this->classroomFilter))
                ->latest('assessed_on')->latest('id')
                ->paginate(15),
            'classrooms' => Classroom::withCount('students')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'competencies' => Competency::all(),
        ];
    }
};
?>

<div>
    <x-page-header title="Assessments & scores" subtitle="Record a task, tick the competencies it assesses, then score each learner 1–3." />

    <div class="grid gap-7 xl:grid-cols-[1fr_24rem] items-start">
        <section>
            <div class="flex gap-3 mb-4">
                <select wire:model.live="classroomFilter" class="select border-ink w-40" aria-label="Filter by class">
                    <option value="">All classes</option>
                    @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="board overflow-x-auto">
                <table class="table">
                    <thead><tr class="border-b border-ink"><th>Date</th><th>Assessment</th><th class="hidden md:table-cell">Competencies</th><th class="text-right">Scored</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($assessments as $a)
                            @php($expected = $a->competencies->count() * ($classrooms->firstWhere('id', $a->classroom_id)?->students_count ?? 0))
                            <tr wire:key="as{{ $a->id }}" class="border-b border-rule hover:bg-base-200">
                                <td class="whitespace-nowrap">{{ $a->assessed_on->format('j M') }}</td>
                                <td><span class="font-bold">{{ $a->title }}</span><span class="block text-sm text-muted">{{ $a->classroom->name }} · {{ $a->subject->name }} · {{ ucfirst($a->type) }}</span></td>
                                <td class="hidden md:table-cell"><span class="flex gap-1">@foreach ($a->competencies as $c)<span class="size-3" style="background: {{ $c->colour }}" title="{{ $c->name }}"></span>@endforeach</span></td>
                                <td @class(['text-right whitespace-nowrap', 'font-bold' => $a->scores_count < $expected])>{{ $a->scores_count }}/{{ $expected }}</td>
                                <td class="text-right"><a href="{{ route('assessments.scores', $a) }}" wire:navigate class="font-bold text-primary underline whitespace-nowrap">{{ $a->scores_count < $expected ? 'Enter scores' : 'Edit' }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center"><p class="font-bold">No assessments yet.</p><p class="text-muted">Add your first one with the form.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $assessments->links() }}</div>
        </section>

        <form wire:submit="create" class="board p-5 xl:sticky xl:top-6">
            <h2 class="display text-2xl mb-4">New assessment</h2>
            <div class="grid gap-3">
                <x-input label="Title" wire:model="title" placeholder="e.g. Activity of integration 3" />
                <div class="grid grid-cols-2 gap-3">
                    <x-select label="Class" wire:model="classroom_id" :options="$classrooms" placeholder="Choose" />
                    <x-select label="Subject" wire:model="subject_id" :options="$subjects" placeholder="Choose" />
                    <x-select label="Type" wire:model="type" :options="collect(\App\Models\Assessment::TYPES)->map(fn ($t) => ['id' => $t, 'name' => ucfirst($t)])" />
                    <x-select label="Term" wire:model="term" :options="[['id' => 1, 'name' => 'Term 1'], ['id' => 2, 'name' => 'Term 2'], ['id' => 3, 'name' => 'Term 3']]" />
                </div>
                <x-input label="Date" type="date" wire:model="assessed_on" />
                <fieldset>
                    <legend class="text-sm font-bold mb-2">Competencies this task assesses</legend>
                    <div class="grid gap-1">
                        @foreach ($competencies as $c)
                            <label class="flex items-center gap-3 px-2 py-1.5 cursor-pointer hover:bg-base-200 has-[:checked]:bg-base-200">
                                <input type="checkbox" wire:model="competency_ids" value="{{ $c->id }}" class="checkbox checkbox-sm border-ink" />
                                <x-competency :competency="$c" class="text-sm" />
                            </label>
                        @endforeach
                    </div>
                    @error('competency_ids')<p class="text-sm text-error mt-1">{{ $message }}</p>@enderror
                </fieldset>
                <x-button type="submit" label="Create and enter scores" spinner="create" class="btn-secondary border-2 border-ink mt-2" />
            </div>
        </form>
    </div>
</div>
