@php
    $stageMission = match ($learnerStage['stage']) {
        'explore', 'E1' => [
            'icon' => '🔎', 'title' => 'Buscá y señalá',
            'action' => 'Con una persona adulta, encontrá tres pistas en la escena: quién se mueve, qué puede cambiar y dónde esperarías.',
            'evidence' => 'Señalá las pistas y contá la decisión con tus propias palabras. Tu acompañante puede ayudarte a escribirla.',
            'success' => 'Reconocés un lugar protegido y esperás antes de actuar.',
        ],
        'discover', 'E2' => [
            'icon' => '🧩', 'title' => 'Armá la secuencia',
            'action' => 'Ordená mentalmente cuatro pasos: detenerte, observar, escuchar y decidir. Explicá qué ocurriría si omitís uno.',
            'evidence' => 'Describí una pista que antes no notabas y el paso que esa pista cambia.',
            'success' => 'Podés explicar la secuencia, no solamente repetir la respuesta.',
        ],
        'understand', 'E3' => [
            'icon' => '🕵️', 'title' => 'Encontrá el riesgo oculto',
            'action' => 'Identificá una suposición peligrosa, una presión posible y una alternativa que conserve tiempo y espacio.',
            'evidence' => 'Defendé tu alternativa usando dos pistas observables de la situación.',
            'success' => 'Distinguís entre lo que sabés, lo que suponés y lo que necesitás confirmar.',
        ],
        'prepare' => [
            'icon' => '🗺️', 'title' => 'Prepará un plan y su alternativa',
            'action' => 'Separá la regla aplicable, el peligro presente y el plan B que usarías si la condición cambia.',
            'evidence' => 'Escribí una decisión principal y el criterio exacto que te haría cambiarla.',
            'success' => 'Tu plan sigue siendo seguro aunque otra persona se equivoque.',
        ],
        'drive' => [
            'icon' => '🔄', 'title' => 'Cambiá de perspectiva',
            'action' => 'Analizá la experiencia como peatón y como conductor. Anticipá qué información podría faltar a cada actor.',
            'evidence' => 'Proponé una conducta propia que vuelva la interacción visible y predecible.',
            'success' => 'Conservás una salida segura frente al error propio o ajeno.',
        ],
        'perfect' => [
            'icon' => '🪞', 'title' => 'Revisá el hábito automático',
            'action' => 'Compará esta conducta con lo que hacés habitualmente. Detectá un atajo mental y cómo lo modelarías mejor.',
            'evidence' => 'Registrá un compromiso observable que puedas demostrar durante una semana.',
            'success' => 'La conducta segura se vuelve consistente y también sirve de ejemplo.',
        ],
        'refresh' => [
            'icon' => '🌿', 'title' => 'Ajustá el recorrido a tus condiciones',
            'action' => 'Evaluá visibilidad, tiempo disponible, comodidad y nivel de esfuerzo. Elegí una alternativa sin prisa.',
            'evidence' => 'Definí una señal personal y una del entorno que te indicarían esperar o cambiar de ruta.',
            'success' => 'Mantenés autonomía sin ignorar cambios personales o del entorno.',
        ],
        default => [
            'icon' => '🧭', 'title' => 'Transferí la decisión',
            'action' => 'Relacioná la experiencia con un recorrido cotidiano y buscá una opción que aumente tiempo, espacio y visibilidad.',
            'evidence' => 'Explicá qué cambiarías y cuál sería la señal para aplicar ese cambio.',
            'success' => 'Podés usar el criterio en una situación diferente.',
        ],
    };
@endphp

<section data-read-aloud class="mt-4 rounded-lg border-2 border-secondary/30 bg-secondary/5 p-4" aria-label="Microreto adaptado a la edad">
    <div class="flex items-start gap-3">
        <span class="text-3xl" aria-hidden="true">{{ $stageMission['icon'] }}</span>
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-secondary">Microreto para {{ $learnerStage['identity'] }}</p>
            <h4 class="mt-1 font-heading text-lg font-bold text-text">{{ $stageMission['title'] }}</h4>
        </div>
    </div>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="rounded-md bg-surface p-3"><p class="text-xs font-bold uppercase text-text-secondary">Tu acción</p><p class="mt-2 text-sm leading-6 text-text">{{ $stageMission['action'] }}</p></div>
        <div class="rounded-md bg-surface p-3"><p class="text-xs font-bold uppercase text-text-secondary">Evidencia esperada</p><p class="mt-2 text-sm leading-6 text-text">{{ $stageMission['evidence'] }}</p></div>
        <div class="rounded-md bg-surface p-3"><p class="text-xs font-bold uppercase text-text-secondary">Lo lograste cuando…</p><p class="mt-2 text-sm leading-6 text-text">{{ $stageMission['success'] }}</p></div>
    </div>
    <p class="mt-3 text-xs leading-5 text-text-secondary"><strong>Objetivo de esta lección:</strong> {{ $design['behavior_objective'] ?? $lesson['summary'] }}</p>
</section>
