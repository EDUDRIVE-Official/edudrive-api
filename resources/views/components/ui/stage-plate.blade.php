@props(['stage', 'size' => 'md', 'current' => false])
{{--
    Placa de etapa curricular. Cada etapa tiene forma y etiqueta propias (rombo, rectángulo,
    círculo, escudo), de modo que el color nunca es la única señal. Decorativa: el nombre y
    las edades van en el texto vecino o en $slot.
--}}
@php
    $code = strtoupper((string) $stage);
    $valid = in_array($code, ['E1', 'E2', 'E3', 'E4'], true);
    $sizes = ['sm' => 'ed-plate--sm', 'md' => '', 'lg' => 'ed-plate--lg'];
@endphp
@if ($valid)
<span {{ $attributes->merge(['class' => trim('ed-plate ed-plate--' . strtolower($code) . ' ' . ($sizes[$size] ?? '') . ($current ? ' ed-plate--actual' : ''))]) }} aria-hidden="true">{{ $code }}</span>
@endif
