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

    /** @var array<int, array<int, string|float|null>> student id => competency id => score */
    public array $levels = [];

    public string $fillValue = '2.0';

    public function mount(): void
    {
        $this->assessment->load('classroom.students', 'competencies', 'subject');
        foreach ($this->assessment->classroom->students as $s) {
            foreach ($this->assessment->competencies as $c) {
                $this->levels[$s->id][$c->id] = null;
            }
        }
        foreach ($this->assessment->scores as $score) {
            $this->levels[$score->student_id][$score->competency_id] = number_format($score->level, 1);
        }
    }

    public function fillBlanks(int $competencyId): void
    {
        $this->validate(['fillValue' => 'required|numeric|between:'.Score::MIN.','.Score::MAX], [], ['fillValue' => 'fill value']);
        foreach ($this->levels as $sid => $row) {
            if ($row[$competencyId] === null || $row[$competencyId] === '') {
                $this->levels[$sid][$competencyId] = number_format((float) $this->fillValue, 1);
            }
        }
    }

    public function save(GapDetector $detector): void
    {
        $this->validate(
            ['levels.*.*' => 'nullable|numeric|between:'.Score::MIN.','.Score::MAX],
            ['levels.*.*.between' => 'Scores must be between 0.9 and 3.0.', 'levels.*.*.numeric' => 'Scores must be numbers, e.g. 2.4.'],
        );

        $before = Alert::open()->inClass($this->assessment->classroom_id)->count();

        DB::transaction(function () use ($detector) {
            foreach ($this->levels as $studentId => $row) {
                foreach ($row as $competencyId => $level) {
                    $key = ['assessment_id' => $this->assessment->id, 'student_id' => $studentId, 'competency_id' => $competencyId];
                    $level === null || $level === ''
                        ? Score::where($key)->delete()
                        : Score::updateOrCreate($key, ['level' => round((float) $level, 1)]);
                }
            }
            $this->assessment->classroom->students->each(fn ($s) => $detector->run($s));
        });

        $change = Alert::open()->inClass($this->assessment->classroom_id)->count() - $before;
        $this->success('Scores saved.', match (true) {
            $change > 0 => "$change new ".str('alert')->plural($change).' raised for '.$this->assessment->classroom->name.'.',
            $change < 0 => abs($change).' '.str('alert')->plural(abs($change)).' resolved.',
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
    <a href="/assessments" wire:navigate class="inline-flex items-center gap-1 text-sm text-navy font-bold mb-4"><x-icon name="o-arrow-left" class="size-4" /> Assessments</a>
    @php($blank = collect($levels)->flatten()->filter(fn ($v) => $v === null || $v === '')->count())

    <x-page-header :title="$assessment->title"
        :subtitle="$assessment->classroom->name.' · '.$assessment->subject->name.' · '.$assessment->assessed_on->format('j M Y')">
        <x-slot:actions>
            <x-button label="Save scores" wire:click="save" spinner="save" icon="o-check" class="btn-primary" />
        </x-slot:actions>
    </x-page-header>

    <div class="panel px-5 py-3 mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <p>Enter a score from <b>0.9</b> to <b>3.0</b> for each learner.</p>
        <p class="flex flex-wrap gap-2"><span class="tag tag-medium">0.9–1.4 Basic</span><span class="tag tag-low">1.5–2.4 Moderate</span><span class="tag tag-good">2.5–3.0 Outstanding</span></p>
        <label class="flex items-center gap-2 ml-auto">Fill blanks with
            <input type="number" step="0.1" min="0.9" max="3" wire:model="fillValue" class="input input-sm w-20" aria-label="Fill value" />
        </label>
    </div>
    @error('fillValue')<p class="text-sm text-error -mt-2 mb-3">{{ $message }}</p>@enderror

    <div class="panel overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th class="sticky left-0 bg-base-100 z-10">Learner</th>
                    @foreach ($assessment->competencies as $c)
                        <th class="min-w-44">
                            <x-competency :competency="$c" class="text-ink" />
                            <button wire:click="fillBlanks({{ $c->id }})" class="block mt-1 font-normal text-navy hover:underline cursor-pointer">Fill blanks</button>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($assessment->classroom->students->sortBy('name') as $s)
                    <tr wire:key="r{{ $s->id }}">
                        <td class="sticky left-0 bg-base-100 z-10 font-bold whitespace-nowrap">{{ $s->name }}</td>
                        @foreach ($assessment->competencies as $c)
                            {{-- Keyed by value so "Fill blanks" re-initialises the live band label. --}}
                            <td wire:key="c{{ $s->id }}-{{ $c->id }}-{{ $levels[$s->id][$c->id] }}" x-data="{ v: @js($levels[$s->id][$c->id]) }">
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.1" min="0.9" max="3" inputmode="decimal"
                                           wire:model="levels.{{ $s->id }}.{{ $c->id }}" x-model="v"
                                           @keydown.enter.prevent="$el.closest('tr').nextElementSibling?.querySelectorAll('input')[{{ $loop->index }}]?.focus()"
                                           @class(['input input-sm w-20 tabular', 'input-error' => $errors->has("levels.{$s->id}.{$c->id}")])
                                           aria-label="{{ $s->name }}, {{ $c->name }}" />
                                    <span class="text-xs w-20" x-text="v === null || v === '' ? '' : (v < 0.9 || v > 3 ? 'Out of range' : (v < 1.5 ? 'Basic' : (v < 2.5 ? 'Moderate' : 'Outstanding')))"
                                          :class="v !== null && v !== '' && (v < 0.9 || v > 3) ? 'text-error font-bold' : 'text-muted'"></span>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($errors->has('levels.*'))<p class="text-sm text-error mt-3">Some scores need fixing: {{ $errors->first('levels.*') }}</p>@endif
    <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
        <p class="{{ $blank ? 'font-bold' : 'text-muted' }}">{{ $blank ? $blank.' '.str('score')->plural($blank).' still blank' : 'Every learner scored' }}</p>
        <x-button label="Save scores" wire:click="save" spinner="save" icon="o-check" class="btn-primary" />
    </div>
</div>
