<?php

use App\Models\Competency;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My progress')] class extends Component {
    public function with(): array
    {
        $student = auth()->user()->student;
        abort_unless($student, 404);

        $levels = $student->levelsByCompetency();
        // Open alerts become "focus areas" here; severity and alert language stay with staff.
        $focusIds = $student->alerts()->open()->pluck('competency_id')->all();

        $cards = Competency::all()->map(function ($c) use ($levels, $focusIds) {
            $l = $levels[$c->id] ?? [];
            $recent = array_slice($l, -5);
            $avg = $recent ? array_sum($recent) / count($recent) : null;

            return [
                'competency' => $c,
                'levels' => $l,
                'focus' => in_array($c->id, $focusIds),
                'word' => match (true) {
                    $avg === null => null,
                    $avg >= 2.5 => 'Outstanding',
                    $avg >= 1.75 => 'Growing well',
                    default => 'Building up',
                },
            ];
        });

        return [
            'student' => $student->load('classroom'),
            'focus' => $cards->where('focus', true)->values(),
            'cards' => $cards,
            'strongest' => $cards->filter(fn ($c) => $c['word'] === 'Outstanding')->count(),
        ];
    }
};
?>

<div>
    <x-page-header :title="'Your progress, '.\Illuminate\Support\Str::before($student->name, ' ')"
        :subtitle="$student->classroom->name.' · your scores on the seven CBC competencies'" />

    @if ($focus->isNotEmpty())
        <section class="mb-8">
            <h2 class="display text-2xl mb-1">Your focus {{ str('area')->plural($focus->count()) }} this term</h2>
            <p class="text-muted mb-4 max-w-[65ch]">Everyone has competencies they are still building. {{ $focus->count() > 1 ? 'Pick one small step from each' : 'Pick one small step' }} and talk to your class teacher about how it went.</p>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($focus as $f)
                    <article class="board">
                        <div class="px-4 py-2 font-bold text-white" style="background: {{ $f['competency']->colour }}">{{ $f['competency']->name }}</div>
                        <div class="p-4">
                            <p>{{ $f['competency']->description }}</p>
                            <p class="mt-3 text-sm"><span class="font-bold">Small step:</span> ask your teacher for one task this week where you can practise this, then look back at how you did.</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @elseif ($cards->whereNotNull('word')->isNotEmpty())
        <div class="board p-5 mb-8">
            <p class="display text-2xl">You are on track in every competency</p>
            <p class="text-muted mt-1">{{ $strongest ? "Outstanding in $strongest. " : '' }}Keep going.</p>
        </div>
    @endif

    <h2 class="display text-2xl mb-3">All seven competencies</h2>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $card)
            <article class="board">
                <div class="h-2" style="background: {{ $card['competency']->colour }}"></div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-bold leading-snug">{{ $card['competency']->name }}</h3>
                        @if ($card['word'])<span class="text-sm font-bold shrink-0">{{ $card['word'] }}</span>@endif
                    </div>
                    @if ($card['levels'])
                        <x-trend :levels="$card['levels']" :benchmark="$card['competency']->benchmark" :colour="$card['competency']->colour" soft class="mt-3" />
                        <p class="text-xs text-muted mt-1">{{ count($card['levels']) }} {{ str('task')->plural(count($card['levels'])) }} · dashed line shows the expected level</p>
                    @else
                        <p class="text-sm text-muted mt-3">No tasks on this competency yet.</p>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</div>
