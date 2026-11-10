@props(['trend'])
@if ($trend)
    @php([$class, $icon, $word] = match ($trend) {
        'improving' => ['tag-good', 'o-arrow-trending-up', 'Improving'],
        'declining' => ['tag-medium', 'o-arrow-trending-down', 'Declining'],
        default => ['tag-low', 'o-arrow-long-right', 'Steady'],
    })
    <span {{ $attributes->class(['tag', $class]) }}><x-icon :name="$icon" class="size-4" />{{ $word }}</span>
@endif
