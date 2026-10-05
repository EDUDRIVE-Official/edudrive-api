@props(['variant' => 'primary', 'size' => 'md', 'disabled' => false])
@php
    // Primario: amarillo de seguridad con texto negro y sombra dura (se "presiona" al pasar y pulsar).
    $variants = [
        'primary' => 'ed-btn-hard bg-accent text-[#14161a] border-border-strong hover:bg-accent',
        'secondary' => 'ed-btn-hard border-border-strong bg-surface text-text hover:bg-background',
        'danger' => 'ed-btn-hard bg-danger text-white border-border-strong hover:bg-danger',
    ];

    $sizes = [
        'sm' => 'min-h-[48px] px-4 text-base',
        'md' => 'min-h-[48px] px-5 text-base',
        'lg' => 'min-h-[52px] px-6 text-lg',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-sm border-[3px] font-sans font-bold '
        . 'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50 '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp
<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }} @disabled($disabled)>
    {{ $slot }}
</button>
