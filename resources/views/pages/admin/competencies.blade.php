<?php

use App\Models\Competency;
use App\Services\GapDetector;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Competencies')] class extends Component {
    use Toast;

    /** @var array<int, array{benchmark: float, description: ?string, intervention: string}> */
    public array $form = [];

    public function mount(): void
    {
        foreach (Competency::all() as $c) {
            $this->form[$c->id] = ['benchmark' => $c->benchmark, 'description' => $c->description, 'intervention' => $c->intervention];
        }
    }

    public function save(int $id): void
    {
        $this->validate([
            "form.$id.benchmark" => 'required|numeric|between:1,3',
            "form.$id.description" => 'nullable|max:500',
            "form.$id.intervention" => 'required|max:1000',
        ], [], ["form.$id.benchmark" => 'benchmark', "form.$id.intervention" => 'recommendation']);

        Competency::findOrFail($id)->update($this->form[$id]);
        $this->success('Saved.', 'Run “Re-check all learners” to apply a new benchmark now, or wait for tonight’s check.');
    }

    public function recheck(GapDetector $detector): void
    {
        $n = $detector->runAll();
        $this->success("Re-checked $n learners.");
    }

    public function with(): array
    {
        return ['competencies' => Competency::all()];
    }
};
?>

<div>
    <x-page-header title="Competencies" subtitle="The NCDC seven. The benchmark is the level (1–3) a learner should average; the recommendation appears on every alert for that competency.">
        <x-slot:actions>
            <x-button label="Re-check all learners" wire:click="recheck" spinner="recheck" icon="o-arrow-path" class="btn-outline border-2 border-ink" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach ($competencies as $c)
            <form wire:submit="save({{ $c->id }})" wire:key="k{{ $c->id }}" class="board">
                <h2 class="px-5 py-2.5 font-bold text-white" style="background: {{ $c->colour }}">{{ $c->name }}</h2>
                <div class="p-5 grid gap-3">
                    <x-input label="Benchmark" type="number" step="0.25" min="1" max="3" wire:model="form.{{ $c->id }}.benchmark" class="max-w-32" />
                    <x-textarea label="What it looks like" wire:model="form.{{ $c->id }}.description" rows="2" />
                    <x-textarea label="Recommended intervention" wire:model="form.{{ $c->id }}.intervention" rows="3" />
                    <x-button type="submit" label="Save" spinner="save({{ $c->id }})" class="btn-secondary border-2 border-ink w-fit" />
                </div>
            </form>
        @endforeach
    </div>
</div>
