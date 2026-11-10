@props(['alert', 'compact' => false])
{{-- One alert: who, which competency, why, and what to do. --}}
<article {{ $attributes->class('panel flex flex-col') }}>
    <div class="flex items-start justify-between gap-3 px-5 pt-4">
        <div>
            <a href="{{ route('alerts.show', $alert) }}" wire:navigate class="heading text-lg hover:underline">{{ $alert->student->name }}</a>
            <p class="text-sm text-muted">{{ $alert->student->classroom->name }} · {{ $alert->student->admission_no }}</p>
        </div>
        <x-severity :level="$alert->severity" />
    </div>
    <div class="px-5 pt-3 flex flex-wrap items-center gap-2">
        <x-competency :competency="$alert->competency" class="font-bold text-sm" />
        @if ($alert->status !== 'open')<x-alert-status :alert="$alert" />@endif
    </div>
    <p class="px-5 pt-2 text-sm">{{ $alert->reasons[0] }}@if (count($alert->reasons) > 1) <span class="text-muted whitespace-nowrap">+{{ count($alert->reasons) - 1 }} more</span>@endif</p>
    @unless ($compact)
        <div class="mx-5 mt-3 mb-5 rounded-md bg-base-200 px-3 py-2.5 text-sm leading-relaxed">
            <p class="font-bold">Recommended action</p>
            <p class="mt-0.5">{{ $alert->recommendation }}</p>
        </div>
    @else
        <div class="pb-4"></div>
    @endunless
</article>
