<?php

use App\Models\Attendance;
use App\Models\Classroom;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Attendance')] class extends Component {
    use Toast;

    #[Url(as: 'class')]
    public ?int $classroomId = null;

    #[Url]
    public string $date = '';

    /** @var array<int, bool> student id => present */
    public array $present = [];

    public bool $recorded = false;

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
        $this->classroomId ??= Classroom::where('class_teacher_id', auth()->id())->value('id') ?? Classroom::orderBy('name')->value('id');
        $this->load();
    }

    public function updated($property): void
    {
        if (in_array($property, ['classroomId', 'date'])) {
            $this->load();
        }
    }

    private function load(): void
    {
        $students = Classroom::find($this->classroomId)?->students()->orderBy('name')->pluck('id') ?? collect();
        $existing = Attendance::whereIn('student_id', $students)->whereDate('date', $this->date)->pluck('present', 'student_id');
        $this->recorded = $existing->isNotEmpty();
        // Unrecorded days start with everyone present: teachers only tap the absentees.
        $this->present = $students->mapWithKeys(fn ($id) => [$id => (bool) ($existing[$id] ?? true)])->all();
    }

    public function save(): void
    {
        $this->validate(['date' => 'required|date|before_or_equal:today', 'classroomId' => 'required|exists:classrooms,id']);
        foreach ($this->present as $studentId => $isPresent) {
            Attendance::updateOrCreate(['student_id' => $studentId, 'date' => $this->date], ['present' => $isPresent]);
        }
        $this->recorded = true;
        $absent = collect($this->present)->reject()->count();
        $this->success('Register saved.', $absent.' absent today.');
    }

    public function with(): array
    {
        return [
            'classrooms' => Classroom::orderBy('name')->get(),
            'students' => Classroom::find($this->classroomId)?->students()->orderBy('name')->get() ?? collect(),
        ];
    }
};
?>

<div>
    @php($absent = collect($present)->reject()->count())
    <x-page-header title="Attendance" :subtitle="$recorded ? 'Register already taken for this day. Changes overwrite it.' : 'Everyone starts present. Tap the learners who are absent.'">
        <x-slot:actions>
            <select wire:model.live="classroomId" class="select w-36" aria-label="Class">
                @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <input type="date" wire:model.live="date" max="{{ now()->toDateString() }}" class="input w-44" aria-label="Date" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($students as $s)
            <label wire:key="p{{ $s->id }}" @class(['panel flex items-center justify-between gap-3 px-4 py-3 cursor-pointer select-none', '!bg-base-200' => ! ($present[$s->id] ?? true)])>
                <span class="font-bold">{{ $s->name }}</span>
                <input type="checkbox" wire:model.live="present.{{ $s->id }}" class="sr-only peer" />
                <span @class(["tag peer-focus-visible:outline-2 peer-focus-visible:outline-navy", ($present[$s->id] ?? true) ? "tag-good" : "tag-high"])>
                    {{ ($present[$s->id] ?? true) ? 'Present' : 'Absent' }}
                </span>
            </label>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-6 border-t border-rule pt-4">
        <p><span class="font-bold">{{ count($present) - $absent }}</span> present · <span class="font-bold">{{ $absent }}</span> absent</p>
        <x-button label="Save register" wire:click="save" spinner="save" icon="o-check" class="btn-primary" />
    </div>
</div>
