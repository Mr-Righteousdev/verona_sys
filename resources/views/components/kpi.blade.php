@props(['label', 'value', 'note' => null, 'href' => null])
<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class(['panel px-4 py-3.5 block', 'hover:border-navy' => $href]) }}>
    <p class="text-sm text-muted">{{ $label }}</p>
    <p class="text-2xl font-bold mt-0.5 tabular">{{ $value }}</p>
    @if ($note)<p class="text-xs text-muted mt-0.5">{{ $note }}</p>@endif
</{{ $href ? 'a' : 'div' }}>
