<x-layouts.app title="EDUDRIVE — Protocolo del piloto controlado">
    @php
        $completedControls = collect(['workspace', 'reviewers', 'reviews', 'candidates'])->filter(fn ($key) => $readiness[$key]['complete'])->count();
    @endphp

    <main id="pilot-main" class="mx-auto max-w-6xl space-y-7">
        <header class="space-y-3">
            <p class="text-sm font-semibold text-primary">Borrador operativo · Primaria 9–12</p>
            <h1 class="text-3xl font-bold">Protocolo para un piloto controlado</h1>
            <p class="max-w-4xl text-text-secondary">Define cómo preparar una futura aplicación supervisada de EduDrive sin exponer a estudiantes al tránsito real. Es una base para revisión institucional: no constituye autorización legal, ética, educativa ni de protección de menores.</p>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" @click="window.print()" class="min-h-11 rounded border border-border px-4 font-semibold">Imprimir protocolo</button><a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('pilot-instruments.pilot-forms') }}">Abrir instrumentos operativos</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-readiness') }}">Volver a preparación</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-review-guide') }}">Guía para especialistas</a></div>
        </header>

        <section class="rounded-2xl border p-6 {{ $readiness['technical_preparation_complete'] ? 'border-blue-400 bg-blue-50 text-blue-950' : 'border-red-400 bg-red-50 text-red-950' }}">
            <p class="text-sm font-semibold uppercase tracking-wide">Condición actual</p>
            <h2 class="mt-1 text-2xl font-bold">{{ $readiness['technical_preparation_complete'] ? 'Puede solicitarse revisión externa' : 'No autorizar participantes' }}</h2>
            <p class="mt-2">EduDrive registra <strong>{{ $completedControls }} de 4 controles internos</strong>. {{ $readiness['technical_preparation_complete'] ? 'Completar esos controles no autoriza ejecutar el piloto: aún deben aprobarse las condiciones externas descritas abajo.' : 'Mientras la preparación técnica esté incompleta, solo se permiten revisiones internas con personas adultas autorizadas y datos ficticios.' }}</p>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Puertas de entrada</p><h2 class="text-2xl font-bold">Todas deben estar documentadas antes de convocar</h2></div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach([
                    ['Preparación interna', 'Curso borrador, tres especialidades, cuatro escenas revisadas y cuatro candidaturas completas.'],
                    ['Mandato institucional', 'Responsable, alcance, centro, población y autoridad que autoriza claramente identificados.'],
                    ['Protección de menores', 'Consentimiento de la persona responsable, asentimiento comprensible y derecho a retirarse sin consecuencia.'],
                    ['Privacidad y datos', 'Variables, finalidad, acceso, conservación, eliminación e incidentes revisados por la instancia competente.'],
                    ['Accesibilidad', 'Tecnologías de apoyo, dispositivos y ajustes razonables probados antes de la sesión.'],
                    ['Respuesta ante incidentes', 'Persona responsable, canal de reporte, criterios de suspensión y atención definidos.'],
                ] as [$title, $description])
                    <article class="rounded-xl border border-border bg-surface p-5"><h3 class="font-bold">{{ $title }}</h3><p class="mt-2 text-text-secondary">{{ $description }}</p></article>
                @endforeach
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Ejecución progresiva</p><h2 class="text-2xl font-bold">Fases que no deben saltarse</h2></div>
            <ol class="space-y-4">
                @foreach([
                    ['Fase 0 · Revisión documental', 'Las instancias responsables revisan el protocolo, las credenciales, los instrumentos y las protecciones. No participan estudiantes.'],
                    ['Fase 1 · Ensayo de mesa', 'Personas adultas del equipo recorren instrucciones, escenas, pausas, ayudas, registro y cierre usando únicamente datos ficticios.'],
                    ['Fase 2 · Accesibilidad y dispositivos', 'Se comprueban teclado, lector de pantalla, contraste, movimiento reducido, audio, reflujo y equipos previstos.'],
                    ['Fase 3 · Aplicación controlada', 'Solo con autorizaciones vigentes, una muestra definida por el diseño aprobado participa en un espacio supervisado y sin exposición vial real.'],
                    ['Fase 4 · Análisis y decisión', 'Se consolidan hallazgos, se corrigen bloqueos y la autoridad responsable decide repetir, ampliar, limitar o detener.'],
                ] as $index => [$title, $description])
                    <li class="flex gap-4"><span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $index }}</span><div><h3 class="font-bold">{{ $title }}</h3><p class="mt-1 text-text-secondary">{{ $description }}</p></div></li>
                @endforeach
            </ol>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <article class="space-y-4 rounded-xl border border-border bg-surface p-5">
                <h2 class="text-xl font-bold">Roles mínimos identificados</h2>
                <dl class="space-y-3"><div><dt class="font-semibold">Responsable del piloto</dt><dd>Coordina autorizaciones, alcance y decisión final.</dd></div><div><dt class="font-semibold">Responsable de protección</dt><dd>Atiende bienestar, retiro, incidentes y comunicación con responsables.</dd></div><div><dt class="font-semibold">Facilitación</dt><dd>Presenta la actividad sin enseñar la respuesta ni presionar.</dd></div><div><dt class="font-semibold">Observación</dt><dd>Registra hechos observables sin interpretar durante la sesión.</dd></div><div><dt class="font-semibold">Custodia de datos</dt><dd>Aplica acceso, conservación y eliminación aprobados.</dd></div></dl>
                <p class="rounded border border-amber-400 bg-amber-50 p-3 text-sm text-amber-950">Una misma persona solo puede asumir varios roles si la institución documenta que no existe conflicto y que puede atenderlos de forma segura.</p>
            </article>
            <article class="space-y-4 rounded-xl border border-border bg-surface p-5">
                <h2 class="text-xl font-bold">Evidencia mínima de la sesión</h2>
                <ul class="list-disc space-y-2 pl-5"><li>Versión exacta de contenidos, escenas y dispositivo.</li><li>Confirmación de autorizaciones, sin copiarlas dentro de notas abiertas.</li><li>Apoyos de acceso utilizados y barreras observadas.</li><li>Decisiones, dudas y solicitudes de ayuda por tarea, sin etiquetar al estudiante.</li><li>Problemas técnicos, interrupciones y criterio aplicado.</li><li>Observaciones del facilitador y devolución voluntaria del participante.</li></ul>
                <p class="rounded border border-blue-300 bg-blue-50 p-3 text-sm text-blue-950"><strong>No registrar por defecto:</strong> imágenes, audio, ubicación precisa, documentos de identidad, diagnósticos ni conversaciones ajenas a la actividad.</p>
            </article>
        </section>

        <section class="space-y-4 rounded-2xl border border-red-400 bg-red-50 p-6 text-red-950">
            <h2 class="text-2xl font-bold">Criterios de suspensión inmediata</h2>
            <ul class="grid gap-3 md:grid-cols-2"><li class="rounded bg-white/80 p-3">Malestar, miedo, cansancio o solicitud de detenerse.</li><li class="rounded bg-white/80 p-3">Falla de acceso que impide comprender o responder.</li><li class="rounded bg-white/80 p-3">La escena enseña o refuerza una conducta vial peligrosa.</li><li class="rounded bg-white/80 p-3">Instrucciones contradictorias o intervención que dirige respuestas.</li><li class="rounded bg-white/80 p-3">Exposición, pérdida o uso no previsto de información.</li><li class="rounded bg-white/80 p-3">Ausencia de la persona responsable o de las condiciones aprobadas.</li></ul>
            <p><strong>Suspender no equivale a fracaso.</strong> Protege a las personas y convierte el incidente en un hallazgo que debe resolverse antes de continuar.</p>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Decisión posterior</p><h2 class="text-2xl font-bold">Resultados permitidos</h2></div>
            <div class="grid gap-4 md:grid-cols-3"><article class="rounded-xl border border-green-400 bg-green-50 p-5 text-green-950"><h3 class="font-bold">Continuar con límites</h3><p class="mt-2">Solo cuando no hay bloqueos y la autoridad documenta alcance, condiciones y siguiente etapa.</p></article><article class="rounded-xl border border-amber-400 bg-amber-50 p-5 text-amber-950"><h3 class="font-bold">Corregir y repetir</h3><p class="mt-2">Cuando los hallazgos son subsanables y requieren una nueva comprobación controlada.</p></article><article class="rounded-xl border border-red-400 bg-red-50 p-5 text-red-950"><h3 class="font-bold">Detener</h3><p class="mt-2">Cuando existe daño, riesgo no controlado, incumplimiento o evidencia insuficiente para avanzar.</p></article></div>
        </section>

        <aside class="rounded-lg border border-border bg-surface p-4"><strong>Límite del documento:</strong> debe ser revisado y adaptado por las autoridades competentes antes de usarse. EduDrive no convierte ninguna casilla, revisión o resultado en autorización automática.</aside>
    </main>
</x-layouts.app>
