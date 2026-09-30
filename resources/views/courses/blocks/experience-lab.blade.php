@php
    $experience = $design['experience_type'] ?? 'story';
    $labs = [
        'visual_exploration' => [
            'icon' => '🔎', 'label' => 'Exploración visual', 'title' => 'Descubrí las pistas antes de decidir',
            'prompt' => 'Avanzá por las tres capas de observación. La escena no cambia; cambia la calidad de la información que reunís.',
            'steps' => [
                ['title' => 'Lo evidente', 'body' => 'Nombrá actores, señales, superficie y condiciones visibles. Evitá interpretar todavía.'],
                ['title' => 'Lo que falta', 'body' => 'Buscá puntos ciegos, sonidos ausentes, trayectorias ocultas y cambios que podrían aparecer.'],
                ['title' => 'Tu margen', 'body' => 'Elegí dónde esperar, qué comprobar y qué evidencia necesitás antes de moverte.'],
            ],
        ],
        'guided_practice' => [
            'icon' => '🧭', 'label' => 'Ensayo guiado', 'title' => 'Convertí la idea en una rutina',
            'prompt' => 'Recorré la secuencia en un espacio protegido. Poder explicar cada paso importa más que hacerlo rápido.',
            'steps' => [
                ['title' => 'Prepará', 'body' => 'Detenete, retirá distractores y reuní únicamente los objetos o apoyos necesarios.'],
                ['title' => 'Comprobá', 'body' => 'Ejecutá la revisión lentamente y explicá qué riesgo detectaría cada paso.'],
                ['title' => 'Decidí', 'body' => 'Definí qué resultado permite continuar y cuál obliga a reparar, esperar o pedir ayuda.'],
            ],
        ],
        'web_simulation' => [
            'icon' => '🎛️', 'label' => 'Simulación', 'title' => 'Cambiá una condición y reconstruí el plan',
            'prompt' => 'Imaginá la misma escena en tres condiciones. Una decisión segura se actualiza cuando cambia el entorno.',
            'steps' => [
                ['title' => 'Condición normal', 'body' => 'Identificá la trayectoria prevista, la visibilidad disponible y un lugar protegido.'],
                ['title' => 'Menos información', 'body' => 'Añadí lluvia, oscuridad, ruido o una obstrucción. Reconocé qué dato dejaste de tener.'],
                ['title' => 'Plan adaptado', 'body' => 'Aumentá tiempo y espacio, reducí velocidad o elegí otra ruta. No compensés incertidumbre con prisa.'],
            ],
        ],
        'dilemma' => [
            'icon' => '⚖️', 'label' => 'Comparador', 'title' => 'Compará consecuencias, no impulsos',
            'prompt' => 'Una alternativa puede ser cómoda o rápida y aun así dejar poco margen. Compará antes de elegir.',
            'steps' => [
                ['title' => 'Opción inmediata', 'body' => 'Reconocé qué ganás y qué información, tiempo o espacio sacrificás al actuar de inmediato.'],
                ['title' => 'Opción protegida', 'body' => 'Buscá la alternativa que permita detenerse, observar y recuperarse de un error ajeno.'],
                ['title' => 'Prueba de margen', 'body' => 'Preguntá: ¿seguiría siendo segura si alguien no me ve, cambia de dirección o se equivoca?'],
            ],
        ],
        'competency_challenge' => [
            'icon' => '🏁', 'label' => 'Misión integradora', 'title' => 'Uní las habilidades en una sola decisión',
            'prompt' => 'No busqués una palabra clave. Construí una respuesta que siga siendo segura si aparece información nueva.',
            'steps' => [
                ['title' => 'Detectá', 'body' => 'Separá hechos, peligros y datos todavía desconocidos.'],
                ['title' => 'Anticipá', 'body' => 'Proponé al menos dos movimientos posibles y una salida protegida.'],
                ['title' => 'Justificá', 'body' => 'Elegí con margen y explicá qué señal te haría detenerte o cambiar el plan.'],
            ],
        ],
    ];
    $lab = $labs[$experience] ?? [
        'icon' => '💡', 'label' => 'Pausa activa', 'title' => 'Del relato a tu vida',
        'prompt' => 'Usá la historia para reconocer una conducta concreta que podés aplicar.',
        'steps' => [
            ['title' => 'Recordá', 'body' => 'Identificá la decisión principal y la pista que permitió tomarla.'],
            ['title' => 'Conectá', 'body' => 'Pensá en una situación cotidiana parecida sin compartir información privada.'],
            ['title' => 'Transferí', 'body' => 'Explicá qué harías, dónde esperarías y cuándo pedirías apoyo.'],
        ],
    ];
@endphp

<section
    class="overflow-hidden rounded-lg border border-primary/25 bg-surface"
    x-data="{ step: 0, visited: [0], steps: @js($lab['steps']), select(index) { this.step = index; if (!this.visited.includes(index)) this.visited.push(index); } }"
    aria-label="{{ $lab['title'] }}"
>
    <header class="flex items-start gap-3 border-b border-border bg-primary/5 px-4 py-3">
        <span class="text-2xl" aria-hidden="true">{{ $lab['icon'] }}</span>
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-primary">{{ $lab['label'] }} · sin calificación</p>
            <h4 class="mt-1 font-heading text-lg font-bold text-text">{{ $lab['title'] }}</h4>
            <p class="mt-1 text-sm leading-6 text-text-secondary">{{ $lab['prompt'] }}</p>
        </div>
    </header>

    <div class="p-4">
        <div class="flex gap-2" role="tablist" aria-label="Etapas del laboratorio">
            @foreach ($lab['steps'] as $index => $step)
                <button
                    type="button"
                    role="tab"
                    class="min-h-11 flex-1 rounded-md border px-2 text-sm font-bold transition-colors"
                    :class="step === {{ $index }} ? 'border-primary bg-primary text-white' : (visited.includes({{ $index }}) ? 'border-success/40 bg-success/10 text-success-text' : 'border-border bg-background text-text-secondary')"
                    :aria-selected="(step === {{ $index }}).toString()"
                    @click="select({{ $index }})"
                >
                    <span class="hidden sm:inline">{{ $index + 1 }}. </span>{{ $step['title'] }}
                </button>
            @endforeach
        </div>

        <div class="mt-4 min-h-32 rounded-lg border border-border bg-background p-5" aria-live="polite">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-primary/10 font-heading text-lg font-bold text-primary" x-text="step + 1"></span>
                <p class="font-heading text-lg font-bold text-text" x-text="steps[step].title"></p>
            </div>
            <p class="mt-3 text-sm leading-6 text-text-secondary" x-text="steps[step].body"></p>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" class="min-h-11 rounded-md border border-border px-4 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40" :disabled="step === 0" @click="select(step - 1)">← Anterior</button>
            <p class="text-center text-xs font-medium text-text-secondary"><span x-text="visited.length"></span> de 3 etapas exploradas</p>
            <button type="button" class="min-h-11 rounded-md bg-primary px-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40" :disabled="step === steps.length - 1" @click="select(step + 1)">Siguiente →</button>
        </div>
    </div>
</section>
