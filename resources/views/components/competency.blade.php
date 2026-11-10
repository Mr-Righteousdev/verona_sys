@props(['competency', 'short' => false])
<span {{ $attributes->class('inline-flex items-center gap-2') }}>
    <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $competency->colour }}"></span>
    <span>{{ $short ? \Illuminate\Support\Str::before($competency->name, ' &') : $competency->name }}</span>
</span>
