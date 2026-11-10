@props(['competency', 'short' => false])
<span {{ $attributes->class('inline-flex items-center gap-2') }}>
    <span class="size-3 shrink-0" style="background: {{ $competency->colour }}"></span>
    <span>{{ $short ? \Illuminate\Support\Str::before($competency->name, ' &') : $competency->name }}</span>
</span>
