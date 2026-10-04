@php
    $earlyStage = in_array($learnerStage['stage'], ['explore', 'discover', 'E1', 'E2'], true);
    $observationCriteria = $earlyStage
        ? [
            'Se detiene en el lugar acordado antes de observar.',
            'Señala personas, vehículos y al menos un peligro.',
            'Explica dónde esperaría o pide ayuda antes de avanzar.',
            'Acepta repetir la secuencia cuando aparece una pista nueva.',
        ]
        : [
            'Identifica la condición visible y una posibilidad oculta.',
            'Compara opciones usando tiempo, espacio y visibilidad.',
            'Explica una decisión principal y un plan alternativo.',
            'Revisa su decisión cuando cambia el entorno o recibe retroalimentación.',
        ];
@endphp

<details class="mt-4 rounded-lg border-2 border-violet-300 bg-violet-50/40 p-4" open>
    <summary class="cursor-pointer font-heading font-bold text-text">Rúbrica para la persona acompañante</summary>
    <p class="mt-2 text-sm leading-6 text-text-secondary">Observá una conducta concreta sin ayudar a elegir la respuesta. La práctica se realiza en un espacio protegido, nunca dentro del tránsito activo.</p>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[640px] border-collapse text-left text-xs">
            <thead><tr class="bg-surface"><th class="border border-border p-2">Conducta observable</th><th class="border border-border p-2">Con ayuda</th><th class="border border-border p-2">En progreso</th><th class="border border-border p-2">De forma autónoma</th><th class="border border-border p-2">No observado</th></tr></thead>
            <tbody>
                @foreach ($observationCriteria as $criterion)
                    <tr><td class="border border-border p-2 font-medium text-text">{{ $criterion }}</td>@for ($column = 0; $column < 4; $column++)<td class="border border-border p-2 text-center"><span class="inline-block h-5 w-5 rounded border border-text-secondary" aria-hidden="true"></span><span class="sr-only">Casilla para marcar</span></td>@endfor</tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 grid gap-3 text-sm md:grid-cols-3">
        <div class="rounded-md bg-surface p-3"><strong class="block text-text">Antes</strong><span class="mt-1 block text-text-secondary">Acordá lugar, límite y señal para detener la práctica.</span></div>
        <div class="rounded-md bg-surface p-3"><strong class="block text-text">Durante</strong><span class="mt-1 block text-text-secondary">Describí solamente lo que viste y la ayuda que fue necesaria.</span></div>
        <div class="rounded-md bg-surface p-3"><strong class="block text-text">Después</strong><span class="mt-1 block text-text-secondary">Preguntá qué pista cambió la decisión y cuál será el próximo intento.</span></div>
    </div>

    <div class="mt-4 rounded-md border border-primary/25 bg-surface p-3 text-sm leading-6 text-text-secondary">
        <strong class="text-text">Cómo registrarla:</strong>
        la persona adulta vinculada ingresa con su propia cuenta a <span class="font-bold text-primary">Mi acompañamiento</span>, selecciona al estudiante y esta lección, y registra contexto, conducta observada, nivel de apoyo y reflexión. El estudiante no puede validar su propia práctica acompañada.
    </div>
    <p class="mt-3 text-xs text-text-secondary">Lección observada: <strong>{{ $lesson['title'] }}</strong> · Objetivo: {{ $design['behavior_objective'] ?? $lesson['summary'] }}</p>
</details>
