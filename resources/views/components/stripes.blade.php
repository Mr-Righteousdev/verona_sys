@props(['severity', 'vertical' => false])
{{-- Severity as painted stripes: count carries meaning, colour reinforces it. --}}
@php($count = ['low' => 1, 'medium' => 2, 'high' => 3][$severity])
@php($paint = ['low' => 'bg-ink', 'medium' => 'bg-paint-yellow ring-1 ring-ink ring-inset', 'high' => 'bg-paint-red'][$severity])
<span {{ $attributes->class(['flex gap-[3px]', 'flex-col w-3' => $vertical, 'inline-flex h-4' => ! $vertical]) }}
      role="img" aria-label="{{ ucfirst($severity) }} severity">
    @for ($i = 0; $i < 3; $i++)
        <span class="{{ $vertical ? 'flex-1' : 'w-[5px]' }} {{ $i < $count ? $paint : 'bg-base-300' }}"></span>
    @endfor
</span>
