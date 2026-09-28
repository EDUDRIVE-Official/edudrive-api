<x-layouts.app title="EDUDRIVE — Incorporación de personas revisoras">
    @php
        $labels = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'];
        $active = $readiness['reviewers']['active_specialties'];
        $profiles = [
            ['education', 'Docencia para primaria', 'Experiencia demostrable con población de 9–12 años, currículo, mediación y evaluación formativa.', 'Edad adecuada, claridad, carga de lectura, secuencia, acompañamiento adulto y evaluación.'],
            ['road_safety', 'Seguridad vial', 'Mandato, formación o experiencia verificable en seguridad vial, movilidad, infraestructura o prevención.', 'Geometría, sentidos, señales, prioridades, trayectorias, responsabilidades y desenlaces.'],
            ['accessibility', 'Accesibilidad', 'Experiencia verificable en accesibilidad digital, educación inclusiva, tecnologías de apoyo o participación de personas usuarias.', 'Equivalencia, navegación, percepción, carga cognitiva, movimiento reducido y ajustes razonables.'],
        ];
    @endphp

    <main id="pilot-main" class="mx-auto max-w-6xl space-y-8">
        <header class="space-y-3 border-b border-border pb-6">
            <p class="text-sm font-semibold text-primary">Anexo I · P912-PILOT-01 · {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Incorporación de personas revisoras</h1>
            <p class="max-w-4xl text-text-secondary">Material para localizar, invitar y verificar personas reales antes de habilitarlas en EduDrive. No asigna cuentas, no envía mensajes y no certifica títulos ni representación institucional.</p>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" @click="window.print()" class="min-h-11 rounded bg-primary px-4 font-semibold text-white">Imprimir kit</button><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-reviewers') }}">Gestionar designaciones verificadas</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.institutional-submission') }}">Volver al expediente</a></div>
        </header>

        <section class="rounded-2xl border border-amber-400 bg-amber-50 p-6 text-amber-950">
            <p class="text-sm font-semibold uppercase tracking-wide">Cobertura actual</p><h2 class="mt-1 text-2xl font-bold">{{ count($active) }} de 3 especialidades con designación activa</h2>
            @if($readiness['reviewers']['missing_specialties'] !== [])<p class="mt-2">Pendientes: {{ collect($readiness['reviewers']['missing_specialties'])->map(fn ($key) => $labels[$key])->join(', ') }}.</p>@else<p class="mt-2">Las tres especialidades tienen designación activa; todavía debe comprobarse externamente la autenticidad de sus respaldos.</p>@endif
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Perfiles mínimos</p><h2 class="text-2xl font-bold">Tres miradas independientes y complementarias</h2></div>
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($profiles as [$key, $title, $qualification, $scope])<article class="rounded-xl border border-border bg-surface p-5"><div class="flex items-start justify-between gap-3"><h3 class="text-xl font-bold">{{ $title }}</h3><span class="rounded-full px-3 py-1 text-sm font-semibold {{ in_array($key, $active, true) ? 'bg-green-100 text-green-900' : 'bg-amber-100 text-amber-900' }}">{{ in_array($key, $active, true) ? 'Designación activa' : 'Pendiente' }}</span></div><h4 class="mt-4 font-semibold">Base que debe comprobarse</h4><p class="mt-1 text-text-secondary">{{ $qualification }}</p><h4 class="mt-4 font-semibold">Alcance principal</h4><p class="mt-1 text-text-secondary">{{ $scope }}</p></article>@endforeach
            </div>
        </section>

        <section class="space-y-5 rounded-2xl border border-border bg-surface p-6">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Plantilla de contacto</p><h2 class="text-2xl font-bold">Invitación a revisión técnica</h2></div>
            <div class="space-y-4 rounded-lg border border-border p-5 leading-relaxed">
                <p><strong>Asunto:</strong> Invitación a revisar el piloto de educación vial EduDrive P912</p>
                <p>Estimada persona profesional:</p>
                <p>EduDrive está preparando una unidad preliminar de educación vial para primaria de 9–12 años, centrada inicialmente en decisiones como peatón y pasajero. Le invitamos a considerar una revisión independiente dentro de su ámbito de competencia.</p>
                <p>La tarea consiste en examinar cuatro escenas visuales y su relación con los objetivos formativos, registrar criterios estructurados y documentar hallazgos accionables. Participar no implica avalar el programa, representar a una institución sin autorización ni aceptar responsabilidad sobre dimensiones fuera de su especialidad.</p>
                <p>Antes de habilitar el acceso solicitaremos confirmación del alcance, disponibilidad, posibles conflictos de interés y una referencia verificable de la base profesional o institucional de la participación. No deben enviarse datos sensibles por canales informales.</p>
                <p>Agradecemos indicar si desea recibir el expediente y las condiciones formales de participación.</p>
                <p>Atentamente,<br>__________________________________________<br>Persona responsable y canal institucional verificado</p>
            </div>
            <p class="text-sm text-text-secondary">Esta plantilla no se envía desde EduDrive. La organización debe usar un canal autorizado y conservar la evidencia de la invitación y aceptación.</p>
        </section>

        <section class="space-y-5 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950">
            <div><p class="text-sm font-semibold uppercase tracking-wide">Aceptación previa</p><h2 class="text-2xl font-bold">Declaración de alcance e independencia</h2></div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach(['Comprendo que revisar no equivale a aprobar el programa completo.', 'Revisaré únicamente la especialidad y versión que consten en mi designación.', 'Declaré relaciones personales, económicas, laborales o institucionales que puedan influir en el criterio.', 'No representaré a una institución sin mandato o autorización verificable.', 'Fundamentaré los cambios requeridos y distinguiré obligación, recomendación y consulta.', 'Protegeré cualquier acceso recibido y no incorporaré datos personales de estudiantes.'] as $statement)<label class="flex gap-3 rounded-lg bg-white/80 p-4"><input class="mt-1" type="checkbox"><span>{{ $statement }}</span></label>@endforeach
            </div>
            <div class="grid gap-6 pt-6 md:grid-cols-2"><p class="border-t border-blue-900 pt-2">Nombre, especialidad y firma</p><p class="border-t border-blue-900 pt-2">Fecha y referencia de aceptación</p></div>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Verificación administrativa</p><h2 class="text-2xl font-bold">Lo que debe comprobarse antes de crear la designación</h2></div>
            <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['1', 'Identidad y cuenta', 'La persona controla una cuenta activa y el contacto coincide con el canal verificado.'],
                    ['2', 'Competencia declarada', 'La experiencia o función guarda relación directa con una sola especialidad asignada.'],
                    ['3', 'Respaldo verificable', 'Existe oficio, expediente, registro profesional, constancia o documento equivalente comprobable.'],
                    ['4', 'Mandato institucional', 'Si actuará en nombre de una institución, esa representación está expresamente autorizada.'],
                    ['5', 'Conflictos y límites', 'Se registran conflictos, recusaciones y aspectos que quedan fuera del criterio.'],
                    ['6', 'Aceptación versionada', 'La persona acepta revisar '.$sceneVersion.' con la guía y el plazo acordados.'],
                ] as [$number, $title, $description])<li class="rounded-xl border border-border bg-surface p-5"><span class="font-bold text-primary">{{ $number }}. {{ $title }}</span><p class="mt-2 text-text-secondary">{{ $description }}</p></li>@endforeach
            </ol>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <h2 class="text-2xl font-bold">Acta mínima de incorporación</h2>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach(['Nombre de la persona', 'Cuenta institucional o verificada', 'Especialidad asignada', 'Organización o ejercicio independiente', 'Base o cualificación comprobada', 'Referencia del respaldo', 'Fecha de aceptación', 'Persona que verificó'] as $field)<label class="space-y-2 font-semibold"><span>{{ $field }}</span><input class="min-h-12 w-full rounded border border-border bg-background px-3" type="text"></label>@endforeach
            </div>
            <label class="block space-y-2 font-semibold"><span>Conflictos, límites o condiciones declaradas</span><textarea class="min-h-28 w-full rounded border border-border bg-background p-3"></textarea></label>
            <div class="grid gap-6 pt-8 md:grid-cols-2"><p class="border-t border-text pt-2">Aceptación de la persona revisora</p><p class="border-t border-text pt-2">Validación administrativa</p></div>
        </section>

        <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Regla de designación:</strong> una invitación, un currículo o una declaración no bastan por sí solos. La persona administradora debe comprobar el respaldo por un medio independiente antes de registrar la designación. EduDrive conserva la referencia declarada, pero no puede certificar su autenticidad.</aside>
    </main>
</x-layouts.app>
