@props(['name', 'size' => 'md'])
{{-- Iconos SVG en línea (trazo 2.2, 24x24). Decorativos por defecto: el texto vecino da el significado. --}}
@php
    $paths = [
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
    'book' => '<path d="M4 5.5C4 4.7 4.7 4 5.5 4H11v16H5.5C4.7 20 4 19.3 4 18.5z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H13v16h5.5c.8 0 1.5-.7 1.5-1.5z"/>',
    'passport' => '<rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M9 16h6"/>',
    'certificate' => '<circle cx="12" cy="9" r="5.5"/><path d="M8.5 13.5 7 21l5-3 5 3-1.5-7.5"/>',
    'progress' => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
    'bell' => '<path d="M6 17v-6a6 6 0 0 1 12 0v6l1.5 2h-15z"/><path d="M10 21h4"/>',
    'more' => '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
    'admin' => '<path d="M12 3 4.5 6v5.5c0 4.5 3 8 7.5 9.5 4.5-1.5 7.5-5 7.5-9.5V6z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
    'moon' => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
    'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
    'logout' => '<path d="M10 4H5v16h5"/><path d="m15 8 4 4-4 4M19 12H9"/>',
    'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
    'warning' => '<path d="M12 3 2.5 20h19z"/><path d="M12 10v5M12 17.5v.5"/>',
    'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
    'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/>',
    'back' => '<path d="m15 5-7 7 7 7"/>',
    'chevron' => '<path d="m9 5 7 7-7 7"/>',
    'edit' => '<path d="m4 20 1-4L16 5l3 3L8 19z"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'organization' => '<path d="M4 21V9l8-5 8 5v12z"/><path d="M9 21v-6h6v6"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'circle' => '<circle cx="12" cy="12" r="8"/>',
    'family' => '<circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.5 2.7-5.5 6-5.5s6 2 6 5.5M15.5 14.5c3 0 5.5 1.5 5.5 5"/>',
    ];
    $sizes = ['sm' => 'h-4 w-4', 'md' => 'h-6 w-6', 'lg' => 'h-8 w-8'];
@endphp
@if (isset($paths[$name]))
<svg {{ $attributes->merge(['class' => 'ed-icon shrink-0 ' . ($sizes[$size] ?? $sizes['md'])]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] !!}</svg>
@endif
