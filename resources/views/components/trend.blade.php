@props(['levels', 'benchmark' => 1.5, 'colour' => '#1f4e99', 'width' => 240, 'height' => 80, 'soft' => false, 'marker' => null])
{{-- Competency scores over time on the 0.9-3.0 CBC scale. Band edges at 1.5 and 2.5, benchmark dashed.
     $marker: index of the first score after an intervention, drawn as a vertical rule. --}}
@php
    $levels = array_values(array_map('floatval', $levels));
    $n = count($levels);
    $w = $width; $h = $height; $pad = 8; $left = 26;
    $x = fn ($i) => $n > 1 ? $left + $i * ($w - $left - $pad) / ($n - 1) : ($left + $w) / 2;
    $y = fn ($v) => $h - $pad - ($v - 0.9) * ($h - 2 * $pad) / 2.1;
    $points = collect($levels)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->implode(' ');
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" {{ $attributes->class('w-full h-auto block') }} role="img"
     aria-label="Scores {{ implode(', ', $levels) }}; benchmark {{ $benchmark }}">
    @foreach ([1.0, 1.5, 2.5, 3.0] as $g) {{-- a list, not keys: PHP truncates float array keys --}}
        <line x1="{{ $left - 4 }}" x2="{{ $w }}" y1="{{ $y($g) }}" y2="{{ $y($g) }}" stroke="#e2e6ed" stroke-width="1" />
        <text x="0" y="{{ $y($g) + 3.5 }}" font-size="10" fill="#556070" font-family="Atkinson Hyperlegible, sans-serif">{{ number_format($g, 1) }}</text>
    @endforeach
    <line x1="{{ $left - 4 }}" x2="{{ $w }}" y1="{{ $y($benchmark) }}" y2="{{ $y($benchmark) }}" stroke="#1b2430" stroke-width="1" stroke-dasharray="4 3" />
    @if ($marker !== null && $marker < $n)
        <line x1="{{ $x(max(0, $marker - 0.5)) }}" x2="{{ $x(max(0, $marker - 0.5)) }}" y1="{{ $pad }}" y2="{{ $h - $pad }}" stroke="#1f4e99" stroke-width="1.5" />
    @endif
    @if ($n > 1)
        <polyline points="{{ $points }}" fill="none" stroke="{{ $colour }}" stroke-width="2" stroke-linejoin="round" />
    @endif
    @foreach ($levels as $i => $v)
        @if ($v < $benchmark && ! $soft)
            <circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="3.5" fill="#fff" stroke="#1b2430" stroke-width="1.75" />
        @else
            <circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="3.5" fill="{{ $colour }}" />
        @endif
    @endforeach
</svg>
