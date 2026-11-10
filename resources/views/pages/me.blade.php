<?php

use App\Models\Competency;
use App\Models\Score;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My progress')] class extends Component {
    public function with(): array
    {
        $student = auth()->user()->student;
        abort_unless($student, 404);

        $summary = $student->competencySummary();
        // Open alerts become "focus areas" here; severity and alert language stay with staff.
        $focusIds = $student->alerts()->open()->pluck('competency_id')->all();

        $cards = Competency::all()->map(fn ($c) => [
            'competency' => $c,
            'summary' => $summary[$c->id] ?? null,
            'focus' => in_array($c->id, $focusIds),
        ]);

        return [
            'student' => $student->load('classroom'),
            'focus' => $cards->where('focus', true)->values(),
            'cards' => $cards,
            'improving' => $cards->filter(fn ($c) => ($c['summary']['trend'] ?? null) === 'improving')->count(),
            'outstanding' => $cards->filter(fn ($c) => $c['summary'] && $c['summary']['average'] >= 2.5)->count(),
        ];
    }
};
?>

<div>
    <x-page-header :title="'Your progress, '.\Illuminate\Support\Str::before($student->name, ' ')"
        :subtitle="$student->classroom->name.' · your scores on the seven CBC competencies (0.9 to 3.0)'" />

    <section class="grid gap-3 grid-cols-2 md:grid-cols-3 mb-7" aria-label="Summary">
        <x-kpi label="Outstanding" :value="$outstanding" note="Competencies at 2.5 or above" />
        <x-kpi label="Improving" :value="$improving" note="Competencies trending up" />
        <x-kpi label="Focus areas" :value="$focus->count()" note="Where to practise next" class="col-span-2 md:col-span-1" />
    </section>

    @if ($focus->isNotEmpty())
        <section class="mb-8" aria-labelledby="focus-h">
            <h2 id="focus-h" class="heading text-lg">Your focus {{ str('area')->plural($focus->count()) }} this term</h2>
            <p class="text-muted mt-1 mb-4 max-w-[65ch]">Everyone has competencies they are still building. {{ $focus->count() > 1 ? 'Pick one small step from each' : 'Pick one small step' }} and talk to your class teacher about how it went.</p>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($focus as $f)
                    <article class="panel p-5">
                        <x-competency :competency="$f['competency']" class="font-bold" />
                        <p class="mt-2">{{ $f['competency']->description }}</p>
                        <p class="mt-3 text-sm"><span class="font-bold">Small step:</span> ask your teacher for one task this week where you can practise this, then look back at how you did.</p>
                        @if (($f['summary']['trend'] ?? null) === 'improving')
                            <p class="mt-3"><span class="tag tag-good">You are already improving here. Keep going.</span></p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <h2 class="heading text-lg mb-3">All seven competencies</h2>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $card)
            @php($s = $card['summary'])
            <article class="panel p-4">
                <div class="flex items-start justify-between gap-3">
                    <x-competency :competency="$card['competency']" class="font-bold leading-snug" />
                    @if ($s)
                        <p class="text-right shrink-0"><span class="text-xl font-bold tabular">{{ number_format($s['average'], 1) }}</span><span class="block text-xs text-muted">{{ Score::band($s['average']) }}</span></p>
                    @endif
                </div>
                @if ($s)
                    <x-trend :levels="$s['levels']" :benchmark="$card['competency']->benchmark" :colour="$card['competency']->colour" soft class="mt-3" />
                    <div class="flex items-center justify-between gap-2 mt-2">
                        <span class="text-xs text-muted">{{ count($s['levels']) }} {{ str('task')->plural(count($s['levels'])) }} · dashed line: expected level</span>
                        @if ($s['trend'] !== 'declining')<x-trend-label :trend="$s['trend']" />@endif
                    </div>
                @else
                    <p class="text-sm text-muted mt-3">No tasks on this competency yet.</p>
                @endif
            </article>
        @endforeach
    </div>
</div>
