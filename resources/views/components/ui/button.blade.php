@props(['variant' => 'primary', 'size' => 'md', 'disabled' => false])
@php
    $variants = [
        'primary' => 'bg-primary text-white hover:bg-secondary',
        'secondary' => 'border border-border bg-surface text-primary hover:bg-background',
        'danger' => 'bg-danger text-white hover:bg-danger/90',
    ];

    $sizes = [
        'sm' => 'min-h-[44px] px-3 text-sm',
        'md' => 'min-h-[44px] px-4 text-base',
        'lg' => 'min-h-[44px] px-6 text-lg',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-sm font-sans font-medium transition '
        . 'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50 '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp
<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }} @disabled($disabled)>
    {{ $slot }}
</button>
