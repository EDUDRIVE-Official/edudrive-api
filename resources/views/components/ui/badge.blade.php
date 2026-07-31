@props(['variant' => 'info'])
@php
    $variants = [
        'success' => 'bg-success/10 text-success',
        'info' => 'bg-info/10 text-info',
        'warning' => 'bg-warning/10 text-warning',
        'danger' => 'bg-danger/10 text-danger',
    ];
@endphp
<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full px-3 py-1 font-sans text-sm font-medium '
        . ($variants[$variant] ?? $variants['info']),
]) }}>
    {{ $slot }}
</span>
