@props(['alert'])
@php($class = match (true) {
    $alert->status === 'resolved' => $alert->outcome === 'improved' || $alert->outcome === 'cleared' ? 'tag-good' : 'tag-low',
    $alert->escalated_at !== null => 'tag-high',
    $alert->status === 'in_progress' => 'tag-info',
    default => 'tag-low',
})
<span {{ $attributes->class(['tag', $class]) }}>{{ $alert->statusLabel() }}</span>
