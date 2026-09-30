<x-layouts.app title="EDUDRIVE — Guía para especialistas">
    @php
        $scenes = [
            ['name' => 'La van que bloquea la vista', 'route' => 'pilot-instruments.van', 'decision' => 'Permanecer en la acera y recuperar visibilidad sin asumir que la vía está libre.', 'risk' => 'Ocultamiento de tránsito por un vehículo detenido.'],
            ['name' => 'El carro que gira', 'route' => 'pilot-instruments.visual', 'decision' => 'Confirmar la trayectoria del vehículo aunque la señal peatonal sea favorable.', 'risk' => 'Conflicto entre prioridad peatonal y maniobra de giro.'],
            ['name' => 'Ruta interrumpida', 'route' => 'pilot-instruments.barrier', 'decision' => 'Retroceder dentro del espacio protegido y cambiar el plan.', 'risk' => 'Acera totalmente bloqueada sin alternativa protegida conocida.'],
            ['name' => 'Cambió el lugar de descenso', 'route' => 'pilot-instruments.descent', 'decision' => 'Permanecer dentro y comunicar que el lugar de descenso no es seguro.', 'risk' => 'Puerta frente a un espacio sin acera.'],
        ];
    @endphp

    <main id="pilot-main" class="mx-auto max-w-6xl space-y-7">
        <header class="space-y-3">
            <p class="text-sm font-semibold text-primary">Paquete interno · Versión {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Guía de revisión para especialistas</h1>
            <p class="max-w-4xl text-text-secondary">Esta guía estandariza la revisión de las cuatro escenas del piloto para primaria de 9–12 años. La persona revisora emite un dictamen interno dentro de su especialidad; no aprueba el curso, no representa automáticamente a su institución y no evalúa estudiantes.</p>
            <div class="flex flex-wrap gap-3 print:hidden">
                <button type="button" @click="window.print()" class="min-h-11 rounded border border-border px-4 font-semibold">Imprimir guía</button>
                <a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('pilot-instruments.visual-review') }}">Ir al registro de revisión</a>
                <a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-readiness') }}">Volver al tablero</a>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-4" aria-label="Secuencia de trabajo">
            @foreach([
                ['1', 'Verificar designación', 'Confirmar especialidad, organización, respaldo y versión asignada.'],
                ['2', 'Explorar la escena', 'Ejecutar todas las decisiones, controles y alternativas accesibles.'],
                ['3', 'Registrar evidencia', 'Documentar criterios y cambios concretos sin datos personales.'],
                ['4', 'Revisar de nuevo', 'Volver a probar cualquier escena corregida antes de cambiar su estado.'],
            ] as [$number, $title, $description])
                <article class="rounded-xl border border-border bg-surface p-4"><span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $number }}</span><h2 class="mt-3 font-bold">{{ $title }}</h2><p class="mt-1 text-sm text-text-secondary">{{ $description }}</p></article>
            @endforeach
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Responsabilidad diferenciada</p><h2 class="text-2xl font-bold">Qué debe comprobar cada especialidad</h2></div>
            <div class="grid gap-5 lg:grid-cols-3">
                <article class="space-y-3 rounded-xl border border-border bg-surface p-5">
                    <h3 class="text-xl font-bold">Docencia</h3>
                    <ul class="list-disc space-y-2 pl-5"><li>La consigna corresponde a una decisión observable y adecuada para 9–12 años.</li><li>El lenguaje, la secuencia y la carga de lectura son comprensibles.</li><li>La devolución explica el criterio sin culpa, miedo ni premio por asumir riesgos.</li><li>La escena enseña lo previsto y no introduce reglas contradictorias.</li></ul>
                </article>
                <article class="space-y-3 rounded-xl border border-border bg-surface p-5">
                    <h3 class="text-xl font-bold">Seguridad vial</h3>
                    <ul class="list-disc space-y-2 pl-5"><li>La geometría, los sentidos de circulación y los carriles son coherentes con el contexto costarricense.</li><li>Señales, semáforos, cruces, aceras y trayectorias representan situaciones posibles.</li><li>Velocidades, distancias, visibilidad y maniobras comunican el riesgo real.</li><li>La opción responsable nunca autoriza una acción insegura por prioridad o cortesía.</li></ul>
                </article>
                <article class="space-y-3 rounded-xl border border-border bg-surface p-5">
                    <h3 class="text-xl font-bold">Accesibilidad</h3>
                    <ul class="list-disc space-y-2 pl-5"><li>Todo el recorrido funciona con teclado y mantiene foco visible y orden lógico.</li><li>La alternativa textual transmite actores, movimiento, riesgo, decisión y resultado.</li><li>Color, sonido o animación no son la única forma de comunicar información.</li><li>Movimiento reducido, pausa, repetición, contraste y narración conservan acceso equivalente.</li></ul>
                </article>
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Aplican a las tres especialidades</p><h2 class="text-2xl font-bold">Cuatro criterios obligatorios</h2></div>
            <dl class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-border p-4"><dt class="font-bold">Fidelidad vial</dt><dd class="mt-1">La infraestructura, los actores y sus movimientos forman una situación coherente.</dd></div>
                <div class="rounded-lg border border-border p-4"><dt class="font-bold">Decisión comprensible</dt><dd class="mt-1">Lo que se observa, lo que se pregunta y las opciones hablan del mismo problema.</dd></div>
                <div class="rounded-lg border border-border p-4"><dt class="font-bold">Consecuencia responsable</dt><dd class="mt-1">La devolución modela autoprotección sin choque, sobresalto, culpa ni falsa seguridad.</dd></div>
                <div class="rounded-lg border border-border p-4"><dt class="font-bold">Acceso equivalente</dt><dd class="mt-1">La experiencia mantiene significado y control sin depender de visión, audio o movimiento.</dd></div>
            </dl>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Muestra completa</p><h2 class="text-2xl font-bold">Recorrido de las cuatro escenas</h2><p>Revisá las cuatro escenas; no seleccionés solo una muestra.</p></div>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full min-w-[760px] border-collapse bg-surface text-left">
                    <thead><tr class="border-b border-border bg-background"><th class="p-4">Escena</th><th class="p-4">Riesgo que debe verse</th><th class="p-4">Decisión que debe enseñarse</th><th class="p-4 print:hidden">Acceso</th></tr></thead>
                    <tbody>@foreach($scenes as $scene)<tr class="border-b border-border last:border-0"><th class="p-4 align-top">{{ $scene['name'] }}</th><td class="p-4 align-top">{{ $scene['risk'] }}</td><td class="p-4 align-top">{{ $scene['decision'] }}</td><td class="p-4 align-top print:hidden"><a target="_blank" rel="noopener" class="font-semibold text-primary underline" href="{{ route($scene['route']) }}">Abrir escena</a></td></tr>@endforeach</tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-2">
            <article class="space-y-3 rounded-xl border border-amber-400 bg-amber-50 p-5 text-amber-950">
                <h2 class="text-xl font-bold">Cómo redactar un hallazgo accionable</h2>
                <ol class="list-decimal space-y-2 pl-5"><li>Indicá qué se observa y en cuál momento.</li><li>Explicá por qué afecta seguridad, aprendizaje o acceso.</li><li>Solicitá un cambio concreto y comprobable.</li><li>Definí qué debe verificarse nuevamente.</li></ol>
                <p class="rounded bg-white/80 p-3 text-sm"><strong>Ejemplo de estructura:</strong> “Al iniciar el giro, el vehículo invade el carril contrario. Ajustar la trayectoria para incorporarlo al carril correcto y volver a comprobar ambos sentidos”.</p>
            </article>
            <article class="space-y-3 rounded-xl border border-blue-300 bg-blue-50 p-5 text-blue-950">
                <h2 class="text-xl font-bold">Cuándo marcar cada estado</h2>
                <p><strong>Requiere cambios:</strong> existe al menos un problema concreto. El hallazgo es obligatorio y bloquea la cobertura.</p>
                <p><strong>Lista para revisión especializada:</strong> los cuatro criterios fueron comprobados en la versión indicada. Significa que esa especialidad no tiene cambios pendientes; no significa “aprobada”.</p>
            </article>
        </section>

        <section class="space-y-3 rounded-2xl border border-green-400 bg-green-50 p-6 text-green-950">
            <h2 class="text-2xl font-bold">Condición para cerrar la revisión interna</h2>
            <p>Cada una de las cuatro escenas debe tener un registro vigente de Docencia, Seguridad vial y Accesibilidad, todos sin cambios pendientes: <strong>12 dictámenes internos trazables</strong>. Solo entonces puede postularse la escena a una lección del curso borrador.</p>
            <p>La revisión interna no reemplaza pruebas con usuarios, validación institucional, aprobación ética, autorización para trabajar con menores ni decisión regulatoria.</p>
            <a class="inline-flex min-h-11 items-center rounded border border-green-800 px-4 font-semibold print:hidden" href="{{ route('pilot-instruments.pilot-protocol') }}">Ver protocolo posterior a la revisión</a>
        </section>
    </main>
</x-layouts.app>
