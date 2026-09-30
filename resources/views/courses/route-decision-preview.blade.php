@php
    $block = ['id' => 'route-decision-preview', 'payload' => [
        'title' => 'Dos caminos a la escuela',
        'context' => 'La ruta corta tiene la acera bloqueada y obliga a caminar cerca de los vehículos. La otra ruta tarda unos minutos más, pero tiene acera continua y cruce marcado.',
        'prompt' => '¿Cuál opción demuestra ciudadanía vial?',
        'choices' => [
            ['id' => 'corta', 'label' => 'Usar la ruta corta porque siempre se ha usado', 'feedback' => 'La costumbre no elimina los obstáculos. Una ruta debe reevaluarse cuando cambian sus condiciones.', 'correct' => false],
            ['id' => 'protegida', 'label' => 'Elegir la ruta con acera continua y cruce marcado', 'feedback' => 'Correcto. Unos minutos adicionales pueden reducir la exposición al riesgo y facilitar el recorrido para todas las personas.', 'correct' => true],
            ['id' => 'calzada', 'label' => 'Bajar a la calzada para rodear el obstáculo', 'feedback' => 'Entrar a la zona vehicular aumenta la exposición. Es preferible buscar una alternativa protegida.', 'correct' => false],
        ],
        'accessible_text' => 'La ruta corta tiene la acera bloqueada y obliga a caminar cerca de los vehículos. La otra ruta tarda unos minutos más, pero tiene acera continua y cruce marcado. ¿Cuál opción demuestra ciudadanía vial? Las opciones y su retroalimentación están disponibles como texto y pueden recorrerse con teclado.',
    ]];
@endphp
<x-layouts.app title="EDUDRIVE — Dos caminos a la escuela"><div class="mx-auto max-w-5xl">@include('courses.blocks.scenario', ['block' => $block])</div></x-layouts.app>
