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
            "form.$id.benchmark" => 'required|numeric|between:0.9,3',
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
    <x-page-header title="Competencies" subtitle="The NCDC seven. Learners averaging below a competency's benchmark (0.9–3.0 scale) are flagged; the recommendation appears on every alert for it.">
        <x-slot:actions>
            <x-button label="Re-check all learners" wire:click="recheck" spinner="recheck" icon="o-arrow-path" class="btn-outline" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach ($competencies as $c)
            <form wire:submit="save({{ $c->id }})" wire:key="k{{ $c->id }}" class="panel">
                <div class="panel-head"><h2 class="heading text-base"><x-competency :competency="$c" /></h2></div>
                <div class="p-5 grid gap-3">
                    <x-input label="Benchmark" type="number" step="0.1" min="0.9" max="3" wire:model="form.{{ $c->id }}.benchmark" class="max-w-32" hint="Learners averaging below this are flagged. 1.5 = start of Moderate." />
                    <x-textarea label="What it looks like" wire:model="form.{{ $c->id }}.description" rows="2" />
                    <x-textarea label="Recommended intervention" wire:model="form.{{ $c->id }}.intervention" rows="3" />
                    <x-button type="submit" label="Save" spinner="save({{ $c->id }})" class="btn-primary w-fit" />
                </div>
            </form>
        @endforeach
    </div>
</div>
