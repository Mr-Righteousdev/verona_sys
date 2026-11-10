@props(['title', 'subtitle' => null])
<header {{ $attributes->class('flex flex-wrap items-end justify-between gap-4 border-b border-ink pb-4 mb-6') }}>
    <div>
        <h1 class="display text-4xl md:text-5xl">{{ $title }}</h1>
        @if ($subtitle)<p class="text-muted mt-2">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
</header>
