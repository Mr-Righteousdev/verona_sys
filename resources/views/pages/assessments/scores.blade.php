<?php

use App\Models\Alert;
use App\Models\Assessment;
use App\Models\Score;
use App\Services\GapDetector;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Assessment $assessment;

    /** @var array<int, array<int, int|null>> student id => competency id => level */
    public array $levels = [];

    public function mount(): void
    {
        $this->assessment->load('classroom.students', 'competencies', 'subject');
        foreach ($this->assessment->classroom->students as $s) {
            foreach ($this->assessment->competencies as $c) {
                $this->levels[$s->id][$c->id] = null;
            }
        }
        foreach ($this->assessment->scores as $score) {
            $this->levels[$score->student_id][$score->competency_id] = $score->level;
        }
    }

    public function fillBlanks(int $competencyId, int $level): void
    {
        foreach ($this->levels as $sid => $row) {
            $this->levels[$sid][$competencyId] ??= $level;
        }
    }

    public function save(GapDetector $detector): void
    {
        $this->validate(['levels.*.*' => 'nullable|integer|between:1,3']);

        $before = Alert::open()->inClass($this->assessment->classroom_id)->count();

        DB::transaction(function () use ($detector) {
            foreach ($this->levels as $studentId => $row) {
                foreach ($row as $competencyId => $level) {
                    $key = ['assessment_id' => $this->assessment->id, 'student_id' => $studentId, 'competency_id' => $competencyId];
                    $level ? Score::updateOrCreate($key, ['level' => $level]) : Score::where($key)->delete();
                }
            }
            $this->assessment->classroom->students->each(fn ($s) => $detector->run($s));
        });

        $change = Alert::open()->inClass($this->assessment->classroom_id)->count() - $before;
        $this->success('Scores saved.', match (true) {
            $change > 0 => "$change new ".str('alert')->plural($change).' raised for '.$this->assessment->classroom->name.'.',
            $change < 0 => abs($change).' '.str('alert')->plural(abs($change)).' cleared.',
            default => 'No change to alerts.',
        });
    }

    public function rendering($view): void
    {
        $view->title('Scores · '.$this->assessment->title);
    }
};
?>

<div>
    <a href="/assessments" wire:navigate class="inline-flex items-center gap-1 text-primary font-bold mb-4"><x-icon name="o-arrow-left" class="size-4" /> Assessments</a>
    @php($blank = collect($levels)->flatten()->filter(fn ($v) => $v === null)->count())

    <x-page-header :title="$assessment->title"
        :subtitle="$assessment->classroom->name.' · '.$assessment->subject->name.' · '.$assessment->assessed_on->format('j M Y').' · 1 Basic, 2 Moderate, 3 Outstanding'">
        <x-slot:actions>
            <x-button label="Save scores" wire:click="save" spinner="save" icon="o-check" class="btn-secondary border-2 border-ink" />
        </x-slot:actions>
    </x-page-header>

    <div class="board overflow-x-auto">
        <table class="table">
            <thead>
                <tr class="border-b border-ink align-bottom">
                    <th class="sticky left-0 bg-base-100 z-10">Learner</th>
                    @foreach ($assessment->competencies as $c)
                        <th class="min-w-48">
                            <span class="block h-1.5 mb-2" style="background: {{ $c->colour }}"></span>
                            <span class="block text-ink">{{ $c->name }}</span>
                            <button wire:click="fillBlanks({{ $c->id }}, 2)" class="mt-1 font-normal text-primary underline cursor-pointer">Fill blanks with 2</button>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($assessment->classroom->students->sortBy('name') as $s)
                    <tr wire:key="r{{ $s->id }}" class="border-b border-rule">
                        <td class="sticky left-0 bg-base-100 z-10 font-bold whitespace-nowrap">{{ $s->name }}</td>
                        @foreach ($assessment->competencies as $c)
                            <td>
                                <div class="inline-flex border-2 border-ink" role="radiogroup" aria-label="{{ $s->name }}, {{ $c->name }}">
                                    @foreach (\App\Models\Score::LEVELS as $value => $label)
                                        <label class="cursor-pointer" title="{{ $label }}">
                                            <input type="radio" class="peer sr-only" value="{{ $value }}" wire:model="levels.{{ $s->id }}.{{ $c->id }}" name="l{{ $s->id }}_{{ $c->id }}" />
                                            <span class="grid place-items-center size-10 font-bold border-ink {{ $value > 1 ? 'border-l' : '' }} hover:bg-base-200 peer-checked:text-white peer-focus-visible:outline-3 peer-focus-visible:outline-ink peer-focus-visible:-outline-offset-4 peer-checked:bg-primary">{{ $value }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
        <p class="{{ $blank ? 'font-bold' : 'text-muted' }}">{{ $blank ? $blank.' '.str('score')->plural($blank).' still blank' : 'Every learner scored' }}</p>
        <x-button label="Save scores" wire:click="save" spinner="save" icon="o-check" class="btn-secondary border-2 border-ink" />
    </div>
</div>
