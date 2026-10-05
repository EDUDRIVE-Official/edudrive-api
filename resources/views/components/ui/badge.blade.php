@props(['variant' => 'info'])
@php
    $variants = [
        'success' => 'border-success bg-success/10 text-success-text',
        'info' => 'border-info bg-info/10 text-info-text',
        'warning' => 'border-warning bg-warning/10 text-warning-text',
        'danger' => 'border-danger bg-danger/10 text-danger-text',
    ];
@endphp
<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full border-2 px-3 py-1 font-sans text-sm font-bold '
        . ($variants[$variant] ?? $variants['info']),
]) }}>
    {{ $slot }}
</span>
