<x-layouts.app title="EDUDRIVE — Expediente institucional P912">
    @php
        $completedControls = collect(['workspace', 'reviewers', 'reviews', 'candidates'])->filter(fn ($key) => $readiness[$key]['complete'])->count();
        $specialties = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'];
    @endphp

    <main id="pilot-main" class="mx-auto max-w-6xl space-y-8">
        <header class="space-y-3 border-b border-border pb-6">
            <p class="text-sm font-semibold text-primary">Expediente preliminar · P912-PILOT-01 · {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Piloto de educación vial para primaria de 9–12 años</h1>
            <p class="max-w-4xl text-text-secondary">Documento de presentación de EduDrive para revisión de autoridades educativas, de seguridad vial y de protección institucional. Describe una propuesta en desarrollo; no acredita eficacia, cumplimiento normativo, aprobación curricular ni autorización para implementarla con estudiantes.</p>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" @click="window.print()" class="min-h-11 rounded bg-primary px-4 font-semibold text-white">Imprimir expediente</button><a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('pilot-instruments.institutional-submission') }}">Versión consolidada</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-readiness') }}">Estado de preparación</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.pilot-protocol') }}">Protocolo</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.pilot-forms') }}">Instrumentos operativos</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.normative-alignment') }}">Matriz normativa</a></div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ficha del proyecto">
            <article class="rounded-xl border border-border bg-surface p-4"><p class="text-sm text-text-secondary">Población priorizada</p><p class="mt-1 text-xl font-bold">Primaria 9–12</p><p class="text-sm">Con acompañamiento adulto.</p></article>
            <article class="rounded-xl border border-border bg-surface p-4"><p class="text-sm text-text-secondary">Alcance inicial</p><p class="mt-1 text-xl font-bold">Peatón y pasajero</p><p class="text-sm">Decisiones en espacios protegidos.</p></article>
            <article class="rounded-xl border border-border bg-surface p-4"><p class="text-sm text-text-secondary">Preparación interna</p><p class="mt-1 text-xl font-bold">{{ $completedControls }} de 4</p><p class="text-sm">No equivale a aprobación.</p></article>
            <article class="rounded-xl border border-border bg-surface p-4"><p class="text-sm text-text-secondary">Estado documental</p><p class="mt-1 text-xl font-bold">Borrador</p><p class="text-sm">Requiere revisión humana externa.</p></article>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.3fr_.7fr]">
            <article class="space-y-4 rounded-2xl border border-border bg-surface p-6">
                <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Resumen ejecutivo</p><h2 class="text-2xl font-bold">Qué propone EduDrive</h2></div>
                <p>Un sistema formativo digital para practicar decisiones viales antes de enfrentarlas en la vida real. El piloto P912 combina situaciones visuales, preguntas abiertas, acompañamiento adulto y comprobaciones separadas para enseñar a observar, reconocer información insuficiente, conservar un espacio protegido, cambiar el plan y comunicar una dificultad.</p>
                <p>No pretende enseñar a niñas y niños a asumir por sí solos la responsabilidad del sistema vial. Las personas conductoras, la infraestructura y las instituciones conservan sus obligaciones. Tampoco sustituye enseñanza presencial, acompañamiento familiar, formación vial oficial ni intervención profesional.</p>
            </article>
            <aside class="space-y-3 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950"><h2 class="text-xl font-bold">Solicitud institucional inicial</h2><ul class="list-disc space-y-2 pl-5"><li>Designar enlaces técnicos verificables.</li><li>Revisar alcance y lenguaje curricular.</li><li>Orientar el mapeo normativo aplicable.</li><li>Revisar protocolo de protección y datos.</li><li>Definir condiciones para un ensayo controlado.</li></ul><p class="font-semibold">No se solicita todavía aprobación nacional ni despliegue con estudiantes.</p></aside>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Resultados formativos previstos</p><h2 class="text-2xl font-bold">Cinco objetivos observables de la unidad modelo</h2></div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Espacio protegido', 'Señala un lugar de espera dentro de la acera y separado del borde.'],
                    ['Visibilidad', 'Identifica qué oculta la vista y qué información todavía no puede comprobar.'],
                    ['Pausa', 'Pospone el avance cuando existe conflicto o información insuficiente.'],
                    ['Comprobación', 'Considera los movimientos que pueden llegar al cruce, incluidos los giros.'],
                    ['Ayuda y cambio de plan', 'Explica cómo pedir acompañamiento o cambiar de plan de forma protegida.'],
                ] as [$title, $description])<article class="rounded-xl border border-border bg-surface p-5"><h3 class="font-bold">{{ $title }}</h3><p class="mt-2 text-text-secondary">{{ $description }}</p></article>@endforeach
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Arquitectura pedagógica</p><h2 class="text-2xl font-bold">Aprender no se reduce a completar una animación</h2></div>
            <ol class="grid gap-4 md:grid-cols-5">
                @foreach([
                    ['1', 'Diagnóstico', 'Primera respuesta sin enseñar el criterio.'],
                    ['2', 'Práctica', 'Decidir, recibir explicación y volver a intentar.'],
                    ['3', 'Transferencia protegida', 'Aplicar en maqueta o espacio cerrado, nunca en tránsito activo.'],
                    ['4', 'Comprobación', 'Resolver situaciones sin pistas previas y con devolución posterior.'],
                    ['5', 'Seguimiento', 'Volver a comprobar más adelante sin declarar retención total con una sola tarea.'],
                ] as [$number, $title, $description])<li class="rounded-lg border border-border p-4"><span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $number }}</span><h3 class="mt-3 font-bold">{{ $title }}</h3><p class="mt-1 text-sm text-text-secondary">{{ $description }}</p></li>@endforeach
            </ol>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Inventario de desarrollo</p><h2 class="text-2xl font-bold">Qué existe hoy y qué demuestra</h2></div>
            <div class="overflow-x-auto rounded-xl border border-border"><table class="w-full min-w-[820px] border-collapse bg-surface text-left"><thead><tr class="bg-background"><th class="p-4">Componente</th><th class="p-4">Estado</th><th class="p-4">Evidencia disponible</th><th class="p-4">Límite</th></tr></thead><tbody>
                @foreach([
                    ['Unidad modelo P912-U01', 'Construida', 'Cinco objetivos, tres prácticas y cinco fases relacionadas.', 'Revisión docente, vial y accesible externa pendiente.'],
                    ['Recorrido de nueve situaciones', 'Ensayo interno', 'Orden, ayudas, cierre y separación de tareas comprobados técnicamente.', 'No demuestra aprendizaje ni validez educativa.'],
                    ['Cuatro escenas visuales 3D', 'Prototipo revisable', 'Decisiones, desenlaces, alternativa textual y controles disponibles.', 'Cobertura especializada pendiente.'],
                    ['Gobierno de revisiones', 'Implementado', 'Designaciones trazables, historial y cobertura por especialidad.', 'Las credenciales deben verificarse externamente.'],
                    ['Curso P912 aislado', 'Borrador', 'Cuatro lecciones sin alterar cursos publicados.', 'Las escenas aún no están integradas como contenido final.'],
                    ['Protocolo e instrumentos', 'Borrador operativo', 'Puertas de entrada, suspensión, observación, incidente y decisión.', 'Requiere adaptación y autorización institucional.'],
                ] as [$component, $status, $evidence, $limit])<tr class="border-t border-border"><th class="p-4 align-top">{{ $component }}</th><td class="p-4 align-top"><span class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-900">{{ $status }}</span></td><td class="p-4 align-top">{{ $evidence }}</td><td class="p-4 align-top">{{ $limit }}</td></tr>@endforeach
            </tbody></table></div>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Estado de preparación</p><h2 class="text-2xl font-bold">Controles internos observables</h2></div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-xl border border-border bg-surface p-4"><h3 class="font-bold">Curso borrador</h3><p class="mt-2 text-2xl font-bold">{{ $readiness['workspace']['lessons'] }} / 4</p><p class="text-sm">Lecciones aisladas.</p></article>
                <article class="rounded-xl border border-border bg-surface p-4"><h3 class="font-bold">Especialidades</h3><p class="mt-2 text-2xl font-bold">{{ count($readiness['reviewers']['active_specialties']) }} / 3</p><p class="text-sm">@if($readiness['reviewers']['missing_specialties'] !== []) Faltan {{ collect($readiness['reviewers']['missing_specialties'])->map(fn ($key) => $specialties[$key])->join(', ') }}.@else Designaciones activas registradas.@endif</p></article>
                <article class="rounded-xl border border-border bg-surface p-4"><h3 class="font-bold">Escenas cubiertas</h3><p class="mt-2 text-2xl font-bold">{{ $readiness['reviews']['completed_scenes'] }} / {{ $readiness['reviews']['total_scenes'] }}</p><p class="text-sm">Con cobertura completa registrada.</p></article>
                <article class="rounded-xl border border-border bg-surface p-4"><h3 class="font-bold">Candidaturas</h3><p class="mt-2 text-2xl font-bold">{{ $readiness['candidates']['linked_scenes'] }} / {{ $readiness['candidates']['total_scenes'] }}</p><p class="text-sm">Vinculadas al borrador correcto.</p></article>
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-amber-400 bg-amber-50 p-6 text-amber-950">
            <h2 class="text-2xl font-bold">Riesgos principales y controles propuestos</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach([
                    ['Transferir responsabilidad al niño', 'Revisar lenguaje y desenlaces; recordar obligaciones de personas adultas, conductoras e instituciones.'],
                    ['Representación vial incorrecta', 'Dictamen especializado por versión; bloquear escenas con hallazgos.'],
                    ['Confundir finalización con dominio', 'Separar práctica, comprobación, transferencia y seguimiento; no emitir aprobación automática.'],
                    ['Excluir por acceso o dispositivo', 'Pruebas reales con tecnologías de apoyo, movimiento reducido y alternativas equivalentes.'],
                    ['Recolectar datos innecesarios', 'Códigos de sesión, minimización, controles institucionales y ausencia de grabación por defecto.'],
                    ['Escalar antes de validar', 'Aplicación progresiva, criterios de suspensión y decisión humana documentada.'],
                ] as [$risk, $control])<article class="rounded-lg bg-white/80 p-4"><h3 class="font-bold">{{ $risk }}</h3><p class="mt-1">{{ $control }}</p></article>@endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Ruta hacia una posible implementación</p><h2 class="text-2xl font-bold">Escalamiento condicionado por evidencia</h2></div>
            <ol class="space-y-3">@foreach([
                ['A', 'Cerrar revisión interna', 'Tres especialidades y cuatro escenas sin bloqueos.'],
                ['B', 'Obtener revisión institucional', 'Currículo, normativa, protección, datos y accesibilidad.'],
                ['C', 'Ensayar con personas adultas', 'Flujo completo, dispositivos, facilitación e incidentes con datos ficticios.'],
                ['D', 'Ejecutar un piloto controlado', 'Solo con autorizaciones y condiciones documentadas.'],
                ['E', 'Corregir y volver a medir', 'Publicar límites, cambios y resultados sin exagerar conclusiones.'],
                ['F', 'Evaluar ampliación', 'La autoridad decide si procede una fase multicentro, nacional o internacional.'],
            ] as [$letter, $title, $description])<li class="flex gap-4 rounded-xl border border-border bg-surface p-4"><span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $letter }}</span><div><h3 class="font-bold">{{ $title }}</h3><p class="mt-1 text-text-secondary">{{ $description }}</p></div></li>@endforeach</ol>
        </section>

        <section class="space-y-4 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950">
            <h2 class="text-2xl font-bold">Índice de anexos disponibles en el sistema</h2>
            <div class="grid gap-3 md:grid-cols-2 print:hidden"><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.unit') }}">A. Unidad modelo y matriz de objetivos</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.visual-sequence') }}">B. Recorrido de escenas visuales</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.visual-review-guide') }}">C. Guía para especialistas</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.visual-review-summary') }}">D. Cobertura y hallazgos</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.pilot-protocol') }}">E. Protocolo controlado</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.pilot-forms') }}">F. Instrumentos operativos</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.normative-alignment') }}">G. Matriz normativa y curricular</a><a class="rounded bg-white p-4 font-semibold underline" href="{{ route('pilot-instruments.institutional-review-package') }}">H. Paquete de revisión institucional</a></div>
            <p class="hidden print:block">Anexos A–H disponibles en el entorno interno de EduDrive; deben exportarse y versionarse conforme al procedimiento institucional acordado.</p>
        </section>

        <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Declaración de estado:</strong> este expediente documenta diseño y preparación técnica. No contiene resultados de estudiantes, dictámenes externos ni evidencia suficiente para afirmar efectividad, impacto o aptitud para despliegue nacional.</aside>
    </main>
</x-layouts.app>
