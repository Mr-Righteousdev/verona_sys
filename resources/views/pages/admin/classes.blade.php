<?php

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Classes & subjects')] class extends Component {
    use Toast;

    public string $className = '';
    public string $level = 'S1';
    public ?int $teacherId = null;
    public string $subjectName = '';

    public function addClass(): void
    {
        $this->validate([
            'className' => 'required|max:40|unique:classrooms,name',
            'level' => 'required|in:S1,S2,S3,S4',
            'teacherId' => 'nullable|exists:users,id',
        ]);
        Classroom::create(['name' => $this->className, 'level' => $this->level, 'class_teacher_id' => $this->teacherId]);
        $this->reset('className', 'teacherId');
        $this->success('Class added.');
    }

    public function setTeacher(int $classroomId, $teacherId): void
    {
        Classroom::findOrFail($classroomId)->update(['class_teacher_id' => $teacherId ?: null]);
        $this->success('Class teacher updated.');
    }

    public function addSubject(): void
    {
        $this->validate(['subjectName' => 'required|max:60|unique:subjects,name']);
        Subject::create(['name' => $this->subjectName]);
        $this->reset('subjectName');
        $this->success('Subject added.');
    }

    public function with(): array
    {
        return [
            'classrooms' => Classroom::withCount('students')->orderBy('name')->get(),
            'teachers' => User::whereIn('role', ['teacher', 'admin'])->orderBy('name')->get(),
            'subjects' => Subject::withCount('assessments')->orderBy('name')->get(),
        ];
    }
};
?>

<div>
    <x-page-header title="Classes & subjects" subtitle="Streams, their class teachers, and the subjects assessments are recorded under." />

    <div class="grid gap-7 xl:grid-cols-2 items-start">
        <section class="board">
            <h2 class="display text-2xl px-5 py-4 border-b border-ink">Classes</h2>
            <table class="table">
                <thead><tr class="border-b border-ink"><th>Class</th><th class="text-right">Learners</th><th>Class teacher</th></tr></thead>
                <tbody>
                    @foreach ($classrooms as $c)
                        <tr wire:key="c{{ $c->id }}" class="border-b border-rule">
                            <td class="font-bold">{{ $c->name }}</td>
                            <td class="text-right">{{ $c->students_count }}</td>
                            <td>
                                <select wire:change="setTeacher({{ $c->id }}, $event.target.value)" class="select select-sm border-ink w-full max-w-56" aria-label="Class teacher for {{ $c->name }}">
                                    <option value="">None</option>
                                    @foreach ($teachers as $t)<option value="{{ $t->id }}" @selected($c->class_teacher_id === $t->id)>{{ $t->name }}</option>@endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <form wire:submit="addClass" class="p-5 border-t border-ink grid gap-3 sm:grid-cols-[1fr_6rem_1fr_auto] items-end">
                <x-input label="New class" wire:model="className" placeholder="S1 North" />
                <x-select label="Level" wire:model="level" :options="collect(['S1', 'S2', 'S3', 'S4'])->map(fn ($l) => ['id' => $l, 'name' => $l])" />
                <x-select label="Class teacher" wire:model="teacherId" :options="$teachers" placeholder="None" />
                <x-button type="submit" label="Add" spinner="addClass" class="btn-secondary border-2 border-ink" />
            </form>
        </section>

        <section class="board">
            <h2 class="display text-2xl px-5 py-4 border-b border-ink">Subjects</h2>
            <ul>
                @foreach ($subjects as $s)
                    <li class="flex justify-between px-5 py-2.5 border-b border-rule"><span>{{ $s->name }}</span><span class="text-muted text-sm">{{ $s->assessments_count }} {{ str('assessment')->plural($s->assessments_count) }}</span></li>
                @endforeach
            </ul>
            <form wire:submit="addSubject" class="p-5 border-t border-ink flex gap-3 items-end">
                <x-input label="New subject" wire:model="subjectName" class="flex-1" />
                <x-button type="submit" label="Add" spinner="addSubject" class="btn-secondary border-2 border-ink" />
            </form>
        </section>
    </div>
</div>
