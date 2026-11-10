@props(['title', 'subtitle' => null])
<header {{ $attributes->class('flex flex-wrap items-end justify-between gap-4 mb-6') }}>
    <div>
        <h1 class="heading text-2xl md:text-[1.75rem]">{{ $title }}</h1>
        @if ($subtitle)<p class="text-muted mt-1">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2 no-print">{{ $actions }}</div>@endisset
</header>
