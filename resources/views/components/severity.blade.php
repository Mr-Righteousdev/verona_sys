@props(['level'])
{{-- Severity is always spelled out; colour only reinforces the word. --}}
<span {{ $attributes->class(['tag', 'tag-'.$level]) }}>{{ ucfirst($level) }}</span>
