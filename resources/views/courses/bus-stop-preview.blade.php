@php
    $block = ['id' => 'bus-stop-preview', 'payload' => [
        'title' => 'El autobús en la parada',
        'context' => 'Un autobús se detiene y varias personas bajan. Luna necesita llegar al otro lado de la calle.',
        'prompt' => '¿Cuál es la decisión más segura?',
        'choices' => [
            ['id' => 'frente', 'label' => 'Cruzar inmediatamente por delante del autobús', 'feedback' => 'El autobús oculta a Luna y también puede impedirle ver vehículos que se aproximan.', 'correct' => false],
            ['id' => 'esperar', 'label' => 'Esperar en la acera y buscar un cruce visible', 'feedback' => 'Correcto. Al esperar y recuperar visibilidad puede comprobar ambos sentidos antes de cruzar.', 'correct' => true],
            ['id' => 'detras', 'label' => 'Cruzar corriendo por detrás del autobús', 'feedback' => 'Detrás del autobús también hay movimientos ocultos y la prisa reduce el tiempo para observar.', 'correct' => false],
        ],
        'accessible_text' => 'Luna permanece en la acera. El autobús bloquea su vista del carril opuesto. Espera a que se retire y busca un paso donde pueda ver y ser vista.',
    ]];
@endphp
<x-layouts.app title="EDUDRIVE — El autobús en la parada"><div class="mx-auto max-w-5xl">@include('courses.blocks.scenario', ['block' => $block])</div></x-layouts.app>
