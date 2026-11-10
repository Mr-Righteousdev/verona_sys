<?php

use App\Models\Classroom;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Learners')] class extends Component {
    use WithPagination;

    #[Url(as: 'class')]
    public ?int $classroomId = null;

    #[Url(as: 'q')]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'students' => Student::query()
                ->with('classroom')
                ->withCount(['alerts as open_alerts_count' => fn ($q) => $q->where('status', 'open')])
                ->withCount(['attendances as days_count' => fn ($q) => $q->where('date', '>=', now()->subDays(30))])
                ->withCount(['attendances as present_count' => fn ($q) => $q->where('date', '>=', now()->subDays(30))->where('present', true)])
                ->when($this->classroomId, fn ($q) => $q->where('classroom_id', $this->classroomId))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('admission_no', 'like', "%{$this->search}%")))
                ->orderByDesc('open_alerts_count')
                ->orderBy('name')
                ->paginate(30),
            'classrooms' => Classroom::orderBy('name')->get(),
        ];
    }
};
?>

<div>
    <x-page-header title="Learners" :subtitle="$students->total().' learners · those with open alerts first'" />

    <div class="flex flex-wrap gap-3 mb-5">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Name or admission number" icon="o-magnifying-glass" class="w-72" aria-label="Search learners" />
        <select wire:model.live="classroomId" class="select w-40" aria-label="Class">
            <option value="">All classes</option>
            @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
    </div>

    <div class="panel overflow-x-auto" wire:loading.class="opacity-60">
        <table class="table">
            <thead><tr class="border-b border-rule"><th>Learner</th><th class="hidden sm:table-cell">Admission no.</th><th>Class</th><th class="text-right">Open alerts</th><th class="text-right hidden md:table-cell">Attendance (30 days)</th></tr></thead>
            <tbody>
                @forelse ($students as $s)
                    <tr wire:key="s{{ $s->id }}" class="border-b border-rule hover:bg-base-200">
                        <td><a href="{{ route('learners.show', $s) }}" wire:navigate class="font-bold hover:underline">{{ $s->name }}</a></td>
                        <td class="hidden sm:table-cell">{{ $s->admission_no }}</td>
                        <td>{{ $s->classroom->name }}</td>
                        <td class="text-right">
                            @if ($s->open_alerts_count)<span class="tag tag-medium">{{ $s->open_alerts_count }}</span>@else<span class="text-muted">0</span>@endif
                        </td>
                        @php($rate = $s->days_count ? round($s->present_count / $s->days_count * 100) : null)
                        <td @class(['text-right hidden md:table-cell', 'font-bold underline decoration-2' => $rate !== null && $rate < 75])>{{ $rate === null ? '—' : $rate.'%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center font-bold">No learners match “{{ $search }}”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $students->links() }}</div>
</div>
