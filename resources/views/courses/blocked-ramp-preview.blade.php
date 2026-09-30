@php
    $block = ['id' => 'blocked-ramp-preview', 'payload' => [
        'title' => 'La rampa está bloqueada',
        'context' => 'Una macetera bloquea la rampa del cruce. Cerca espera una persona que usa silla de ruedas y la calzada tiene tránsito.',
        'prompt' => '¿Qué acción respeta autonomía y seguridad?',
        'choices' => [
            ['id' => 'empujar', 'label' => 'Empujar la silla sin preguntar para pasar rápido', 'feedback' => 'Ayudar sin consentimiento puede ser inseguro y no respeta la autonomía de la persona.', 'correct' => false],
            ['id' => 'preguntar', 'label' => 'Preguntar si necesita apoyo y buscar juntos una alternativa protegida', 'feedback' => 'Correcto. Se ofrece apoyo con consentimiento y se evita convertir la calzada en una solución improvisada.', 'correct' => true],
            ['id' => 'calzada', 'label' => 'Indicarle que rodee la macetera por la calzada', 'feedback' => 'La obstrucción no debe resolverse exponiendo a la persona al tránsito. También conviene reportarla por un canal seguro.', 'correct' => false],
        ],
        'accessible_text' => 'Una macetera bloquea la rampa del cruce. Una persona que usa silla de ruedas espera cerca y hay tránsito. Se debe preguntar si desea apoyo y buscar juntos una alternativa protegida.',
    ]];
@endphp
<x-layouts.app title="EDUDRIVE — La rampa está bloqueada"><div class="mx-auto max-w-5xl">@include('courses.blocks.scenario', ['block' => $block])</div></x-layouts.app>
