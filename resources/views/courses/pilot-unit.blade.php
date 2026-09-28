<x-layouts.app title="EDUDRIVE — Unidad modelo P912">
    <article class="mx-auto max-w-3xl space-y-6">
        <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.index') }}">Volver al ensayo de instrumentos</a>
        <header class="space-y-3">
            <a class="inline-block rounded bg-primary p-3 font-semibold text-white" href="{{ route('pilot-instruments.visual-sequence') }}">Abrir el recorrido visual de cuatro prácticas</a>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.visual-review') }}">Revisar calidad de las cuatro escenas</a>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.barrier') }}">Probar la escena: Ruta interrumpida</a>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.descent') }}">Probar la escena: Cambió el lugar de descenso</a>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.van') }}">Probar la escena: La van que bloquea la vista</a>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.visual') }}">Probar una escena visual: Luna y el carro que gira</a>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.journey') }}">Ensayar la vista del estudiante con datos ficticios</a>
            <p>Unidad {{ $unit['id'] }} · Versión {{ $unit['version'] }} · Guía docente en revisión</p>
            <h1 class="text-2xl font-bold">{{ $unit['title'] }}</h1>
            <p>{{ $unit['audience'] }}</p>
            <p>{{ $unit['purpose'] }}</p>
            <p class="rounded-lg border border-warning p-4">No mostrar esta guía antes del diagnóstico: contiene enseñanza y orientaciones. Material interno pendiente de revisión pedagógica, vial y accesible; no implica aprobación institucional.</p>
            <p>{{ $unit['scope'] }}</p>
        </header>
        <nav aria-label="Secciones de la unidad" class="flex flex-wrap gap-3">
            <a class="rounded border border-border p-3 underline" href="#objetivos">Objetivos</a>
            <a class="rounded border border-border p-3 underline" href="#secuencia">Secuencia</a>
            <a class="rounded border border-border p-3 underline" href="#actividades">Actividades</a>
            <a class="rounded border border-border p-3 underline" href="#acceso">Accesibilidad</a>
        </nav>
        <section id="objetivos" class="space-y-3">
            <h2 class="text-xl font-bold">Qué queremos observar</h2>
            @foreach($unit['goals'] as $goal)
                <div class="rounded-lg border border-border bg-surface p-4">
                    <h3 class="font-semibold">{{ $goal['text'] }}</h3>
                    <p class="mt-2 text-sm">{{ $goal['indicator'] }} · Práctica {{ $goal['practice'] }} · Evidencias {{ implode(', ', $goal['evidence']) }} · Transferencia {{ $goal['transfer'] }}</p>
                </div>
            @endforeach
        </section>
        <section id="secuencia" class="space-y-4">
            <h2 class="text-xl font-bold">Recorrido de aprendizaje</h2>
            <p>Distribuí la secuencia en varios encuentros breves. Los tiempos son orientativos y no se usan como criterio de logro.</p>
            @foreach($unit['phases'] as $phase)
                <section class="space-y-2 rounded-lg border border-border bg-surface p-4">
                    <h3 class="font-semibold">{{ $phase['title'] }}</h3>
                    <p class="text-sm">{{ $phase['duration'] }}</p>
                    <p>{{ $phase['instruction'] }}</p>
                    <p><strong>Qué registrar:</strong> {{ $phase['record'] }}</p>
                </section>
            @endforeach
            <p class="rounded-lg border border-border p-4">El ensayo actual ofrece las formas completas del piloto, incluidos los ítems de pasajeros. Esta guía selecciona objetivos de peatones; no cambia ni filtra esas formas. No interpretar sus resultados globales como resultados de esta unidad.</p>
        </section>
        <section id="actividades" class="space-y-4">
            <h2 class="text-xl font-bold">Tres prácticas guiadas</h2>
            @foreach($unit['activities'] as $activity)
                <section class="space-y-3 rounded-lg border border-border bg-surface p-5">
                    <h3 class="text-lg font-semibold">{{ $activity['id'] }} · {{ $activity['title'] }}</h3>
                    <p><strong>Preparación:</strong> {{ $activity['setup'] }}</p>
                    <p><strong>Consigna:</strong> {{ $activity['prompt'] }}</p>
                    <details class="rounded border border-border p-3">
                        <summary class="cursor-pointer">{{ $activity['id'] }} · Ayuda y explicación para después de la primera respuesta</summary>
                        <p class="mt-3"><strong>Pista:</strong> {{ $activity['hint'] }}</p>
                        <p class="mt-3">{{ $activity['explanation'] }}</p>
                    </details>
                    <p><strong>Variación:</strong> {{ $activity['variation'] }}</p>
                </section>
            @endforeach
        </section>
        <section id="acceso" class="space-y-3">
            <h2 class="text-xl font-bold">Participar de distintas maneras</h2>
            <ul class="list-disc space-y-3 pl-6">@foreach($unit['accessibility'] as $support)<li>{{ $support }}</li>@endforeach</ul>
            <p>La revisión humana sigue pendiente. Esta página no registra respuestas, no califica y no modifica cursos ni certificados.</p>
        </section>
    </article>
</x-layouts.app>
