@props(['alert', 'compact' => false])
{{-- The signature board: learner band, competency band in its paint, intervention band. --}}
{{-- Subgrid rows keep the bands of neighbouring boards level when names wrap. --}}
<article {{ $attributes->class(['board relative grid grid-rows-subgrid mb-5', $compact ? 'row-span-3' : 'row-span-4', 'opacity-80' => $alert->status === 'resolved']) }}>
    <x-stripes :severity="$alert->severity" vertical class="absolute -top-[2px] -bottom-[2px] -right-[2px]" />
    <a href="{{ route('alerts.show', $alert) }}" wire:navigate class="block px-4 pt-4 pb-3 pr-8 hover:bg-base-200">
        <h3 class="display text-2xl">{{ $alert->student->name }}</h3>
        <p class="text-sm text-muted mt-1">{{ $alert->student->classroom->name }} · {{ $alert->student->admission_no }}</p>
    </a>
    <div class="px-4 py-2 font-bold text-[0.9375rem] mr-[10px] {{ $alert->status === 'resolved' ? 'border-y border-ink' : 'text-white' }}"
         style="{{ $alert->status === 'resolved' ? '' : 'background:'.$alert->competency->colour }}">
        {{ $alert->competency->name }}
    </div>
    <p class="px-4 py-2.5 text-sm border-b border-rule mr-[10px]">{{ $alert->reasons[0] }}@if (count($alert->reasons) > 1) <span class="text-muted whitespace-nowrap">+{{ count($alert->reasons) - 1 }} more</span>@endif</p>
    @unless ($compact)
        <div class="px-4 pt-2.5 pb-4 text-sm leading-relaxed mr-[10px]">
            <p class="text-xs font-bold uppercase tracking-wider">Do this</p>
            <p class="mt-0.5">{{ $alert->recommendation }}</p>
        </div>
    @endunless
</article>
