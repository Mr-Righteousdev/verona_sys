@props(['levels', 'benchmark' => 2.0, 'colour' => '#16348f', 'width' => 240, 'height' => 72, 'soft' => false])
{{-- Competency levels over time on the 1-3 CBC scale, with the benchmark ruled across. --}}
@php
    $levels = array_values($levels);
    $n = count($levels);
    $w = $width; $h = $height; $pad = 8; $left = 18;
    $x = fn ($i) => $n > 1 ? $left + $i * ($w - $left - $pad) / ($n - 1) : $w / 2;
    $y = fn ($v) => $h - $pad - ($v - 1) * ($h - 2 * $pad) / 2;
    $points = collect($levels)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->implode(' ');
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" {{ $attributes->class('w-full h-auto block') }} role="img"
     aria-label="Levels {{ implode(', ', $levels) }}; benchmark {{ $benchmark }}">
    @foreach ([1, 2, 3] as $g)
        <line x1="{{ $left - 6 }}" x2="{{ $w }}" y1="{{ $y($g) }}" y2="{{ $y($g) }}" stroke="#d3d8e2" stroke-width="1" />
        <text x="0" y="{{ $y($g) + 4 }}" font-size="11" fill="#4a5160" font-family="Atkinson Hyperlegible, sans-serif">{{ $g }}</text>
    @endforeach
    <line x1="{{ $left - 6 }}" x2="{{ $w }}" y1="{{ $y($benchmark) }}" y2="{{ $y($benchmark) }}" stroke="#111" stroke-width="1" stroke-dasharray="4 3" />
    @if ($n > 1)
        <polyline points="{{ $points }}" fill="none" stroke="{{ $colour }}" stroke-width="2.5" stroke-linejoin="round" />
    @endif
    @foreach ($levels as $i => $v)
        @if ($v < $benchmark && ! $soft)
            <rect x="{{ $x($i) - 3.5 }}" y="{{ $y($v) - 3.5 }}" width="7" height="7" fill="#fff" stroke="#111" stroke-width="2" />
        @else
            <rect x="{{ $x($i) - 4 }}" y="{{ $y($v) - 4 }}" width="8" height="8" fill="{{ $colour }}" />
        @endif
    @endforeach
</svg>
