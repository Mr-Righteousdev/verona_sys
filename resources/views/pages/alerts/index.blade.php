<?php

use App\Models\Alert;
use App\Models\Classroom;
use App\Models\Competency;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Alerts')] class extends Component {
    use WithPagination;

    #[Url(as: 'class')]
    public ?int $classroomId = null;

    #[Url(as: 'competency')]
    public ?int $competencyId = null;

    #[Url]
    public string $severity = '';

    #[Url]
    public string $status = 'unresolved';

    #[Url]
    public bool $escalated = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $alerts = Alert::query()
            ->with('student.classroom', 'competency')
            ->inClass($this->classroomId)
            ->when($this->competencyId, fn ($q) => $q->where('competency_id', $this->competencyId))
            ->when($this->severity, fn ($q) => $q->where('severity', $this->severity))
            ->when($this->status === 'unresolved', fn ($q) => $q->open())
            ->when(in_array($this->status, ['open', 'in_progress', 'resolved']), fn ($q) => $q->where('status', $this->status))
            ->when($this->escalated, fn ($q) => $q->whereNotNull('escalated_at')->open())
            ->urgentFirst()
            ->paginate(25);

        return [
            'alerts' => $alerts,
            'classrooms' => Classroom::orderBy('name')->get(),
            'competencies' => Competency::all(),
        ];
    }
};
?>

<div>
    <x-page-header title="Alerts" :subtitle="$alerts->total().' '.str('alert')->plural($alerts->total()).', most urgent first'" />

    <div class="flex flex-wrap items-center gap-3 mb-5" role="group" aria-label="Filters">
        <select wire:model.live="classroomId" class="select w-40" aria-label="Class">
            <option value="">All classes</option>
            @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <select wire:model.live="competencyId" class="select w-64" aria-label="Competency">
            <option value="">All competencies</option>
            @foreach ($competencies as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <select wire:model.live="severity" class="select w-40" aria-label="Severity">
            <option value="">Any severity</option>
            <option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option>
        </select>
        <select wire:model.live="status" class="select w-56" aria-label="Status">
            <option value="unresolved">All unresolved</option>
            <option value="open">Needs action</option>
            <option value="in_progress">Intervention under way</option>
            <option value="resolved">Resolved</option>
            <option value="">All</option>
        </select>
        <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" wire:model.live="escalated" class="checkbox checkbox-sm" /> Escalated only</label>
    </div>

    <div class="panel overflow-x-auto" wire:loading.class="opacity-60">
        <table class="table">
            <thead>
                <tr class="border-b border-rule">
                    <th>Learner</th>
                    <th>Competency</th>
                    <th class="hidden lg:table-cell">Why</th>
                    <th class="text-right">Recent avg</th>
                    <th>Severity</th>
                    <th class="hidden md:table-cell">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr wire:key="al{{ $alert->id }}" class="border-b border-rule hover:bg-base-200">
                        <td>
                            <a href="{{ route('alerts.show', $alert) }}" wire:navigate class="font-bold hover:underline">{{ $alert->student->name }}</a>
                            <span class="block text-sm text-muted">{{ $alert->student->classroom->name }}</span>
                        </td>
                        <td><x-competency :competency="$alert->competency" /></td>
                        <td class="hidden lg:table-cell text-sm max-w-[40ch]">{{ $alert->reasons[0] }}</td>
                        <td class="text-right font-bold">{{ number_format($alert->recent_average, 2) }}</td>
                        <td><x-severity :level="$alert->severity" /></td>
                        <td class="hidden md:table-cell"><x-alert-status :alert="$alert" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center">
                        <p class="font-bold">No alerts match these filters.</p>
                        <button wire:click="$set('status', ''); $set('severity', ''); $set('competencyId', null); $set('classroomId', null); $set('escalated', false)" class="text-primary underline mt-1 cursor-pointer">Clear filters</button>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $alerts->links() }}</div>
</div>
