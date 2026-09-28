@php
    $block = ['id' => 'crossing-movements-preview', 'payload' => [
        'title' => 'Movimientos que se cruzan',
        'context' => 'Luna espera en la acera. Un automóvil sale lentamente de un garaje y una bicicleta se acerca por el borde de la vía casi sin hacer ruido.',
        'prompt' => '¿Qué debe hacer antes de continuar?',
        'choices' => [
            ['id' => 'solo-carro', 'label' => 'Esperar únicamente a que pase el automóvil', 'feedback' => 'El automóvil no es el único actor. La bicicleta también puede cruzar su trayectoria.', 'correct' => false],
            ['id' => 'revisar-todos', 'label' => 'Permanecer protegida y volver a comprobar ambas trayectorias', 'feedback' => 'Correcto. Reconocer a todos los actores y anticipar sus movimientos evita decidir con información incompleta.', 'correct' => true],
            ['id' => 'avisar', 'label' => 'Entrar a la vía y hacer señas para que ambos se detengan', 'feedback' => 'Hacerse visible ayuda, pero no justifica abandonar el espacio protegido cuando todavía hay trayectorias activas.', 'correct' => false],
        ],
        'accessible_text' => 'Luna permanece en la acera. Un automóvil cruza la acera al salir de un garaje y una bicicleta se acerca por el borde de la vía. Debe comprobar ambas trayectorias antes de continuar.',
    ]];
@endphp
<x-layouts.app title="EDUDRIVE — Movimientos que se cruzan"><div class="mx-auto max-w-5xl">@include('courses.blocks.scenario', ['block' => $block])</div></x-layouts.app>
