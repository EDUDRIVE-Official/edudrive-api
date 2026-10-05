@props(['name' => 'ruta'])
{{-- Pictogramas de curso (48x48, trazo 2.6): peatón, bicicleta, bus, moto, auto, hoja, escuela, emergencia y ruta. Decorativos. --}}
@php
    $paths = [
        'peaton' => '<circle cx="24" cy="9" r="4"/><path d="M24 15v12M24 19l-7 5M24 19l7 5M24 27l-5 8M24 27l5 8"/><path d="M5 43h8M20 43h8M35 43h8"/>',
        'bici' => '<circle cx="11" cy="32" r="7"/><circle cx="37" cy="32" r="7"/><path d="M11 32l8-14h11l7 14M19 18l6 14M17 12h6M30 18l-2-6h5"/>',
        'bus' => '<rect x="7" y="8" width="34" height="28" rx="5"/><path d="M7 22h34M7 29h34"/><circle cx="15" cy="40" r="3"/><circle cx="33" cy="40" r="3"/>',
        'moto' => '<circle cx="10" cy="33" r="6"/><circle cx="38" cy="33" r="6"/><path d="M10 33l9-11h11l8 11M30 22l-3-9h6M19 22l-4-4"/>',
        'auto' => '<path d="M5 32l4-11c.6-1.6 2-2.5 3.6-2.5h22.8c1.6 0 3 .9 3.6 2.5l4 11v7H5z"/><circle cx="14" cy="36" r="3"/><circle cx="34" cy="36" r="3"/><path d="M10 25h28"/>',
        'hoja' => '<path d="M8 40C8 21 21 9 40 9c0 19-11 31-32 31z"/><path d="M8 40l20-20"/>',
        'escuela' => '<path d="M6 41V20L24 9l18 11v21z"/><path d="M19 41V28h10v13"/>',
        'emergencia' => '<path d="M24 6 4 42h40z"/><path d="M24 19v11M24 36v1"/>',
        'ruta' => '<path d="M16 42 21 6M32 42 27 6"/><path d="M24 10v6M24 22v6M24 34v6"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'ed-pictogram']) }} viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? $paths['ruta'] !!}</svg>
