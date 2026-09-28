<x-layouts.app title="EDUDRIVE — Matriz normativa y curricular P912">
    <main id="pilot-main" class="mx-auto max-w-7xl space-y-8">
        <header class="space-y-3 border-b border-border pb-6">
            <p class="text-sm font-semibold text-primary">Anexo G · P912-PILOT-01</p>
            <h1 class="text-3xl font-bold">Matriz normativa y curricular preliminar</h1>
            <p class="max-w-5xl text-text-secondary">Relaciona fuentes oficiales de Costa Rica con evidencia existente en EduDrive y con las decisiones externas todavía pendientes. Es una herramienta de trabajo para revisión institucional; no es un dictamen jurídico, una aprobación curricular ni un aval de autoridad.</p>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" @click="window.print()" class="min-h-11 rounded bg-primary px-4 font-semibold text-white">Imprimir matriz</button><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.institutional-review-package') }}">Preparar revisión institucional</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.pilot-dossier') }}">Volver al expediente</a></div>
        </header>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-border bg-surface p-5"><p class="text-sm text-text-secondary">Fuentes oficiales verificadas</p><p class="mt-1 text-3xl font-bold">{{ count($sources) }}</p><p class="text-sm">Consulta realizada el {{ $verifiedOn }}.</p></article>
            <article class="rounded-xl border border-border bg-surface p-5"><p class="text-sm text-text-secondary">Tipo de conclusión</p><p class="mt-1 text-xl font-bold">Correspondencia preliminar</p><p class="text-sm">No certifica cumplimiento.</p></article>
            <article class="rounded-xl border border-amber-400 bg-amber-50 p-5 text-amber-950"><p class="text-sm">Decisión indispensable</p><p class="mt-1 text-xl font-bold">Revisión externa</p><p class="text-sm">MEP, DGEV, COSEVI, accesibilidad y asesoría jurídica.</p></article>
        </section>

        <section class="space-y-3 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950">
            <p class="text-sm font-semibold uppercase tracking-wide">Control temporal de la norma</p>
            <h2 class="text-2xl font-bold">La versión vigente y la reforma futura no se mezclan</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <article class="rounded-lg bg-white/80 p-4"><h3 class="font-bold">Hasta el 19 de diciembre de 2026</h3><p class="mt-2">La referencia de trabajo es el artículo 217 actualmente vigente de la Ley 9078.</p></article>
                <article class="rounded-lg bg-white/80 p-4"><h3 class="font-bold">Desde el 20 de diciembre de 2026</h3><p class="mt-2">La nota oficial anuncia la entrada en vigor de la reforma de la Ley 10834, con movilidad segura, accesible e inclusiva, movilidad activa, pensamiento crítico y atención a modos vulnerables. EduDrive puede prepararse, pero no debe presentarla como vigente antes de esa fecha.</p></article>
            </div>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Trazabilidad</p><h2 class="text-2xl font-bold">Qué exige la referencia, qué existe y qué falta</h2></div>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full min-w-[1180px] border-collapse bg-surface text-left">
                    <thead><tr class="bg-background"><th class="p-4">Fuente oficial</th><th class="p-4">Expectativa identificada</th><th class="p-4">Evidencia EduDrive</th><th class="p-4">Lectura</th><th class="p-4">Brecha o decisión pendiente</th></tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr class="border-t border-border">
                                <th class="p-4 align-top">{{ $row['source'] }}</th>
                                <td class="p-4 align-top">{{ $row['expectation'] }}</td>
                                <td class="p-4 align-top">{{ $row['evidence'] }}</td>
                                <td class="p-4 align-top"><span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-900">{{ $row['status'] }}</span></td>
                                <td class="p-4 align-top">{{ $row['gap'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Lectura curricular responsable</p><h2 class="text-2xl font-bold">Lo demostrado, lo inferido y lo que solo una autoridad puede decidir</h2></div>
            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border border-green-300 bg-green-50 p-4 text-green-950"><h3 class="font-bold">Verificado en fuentes</h3><p class="mt-2">La educación vial es obligatoria; incluye varios roles viales, convivencia, buenas prácticas y necesidades de personas con discapacidad.</p></article>
                <article class="rounded-lg border border-blue-300 bg-blue-50 p-4 text-blue-950"><h3 class="font-bold">Correspondencia de diseño</h3><p class="mt-2">Los objetivos P912 guardan relación con esas finalidades, pero esa relación todavía debe ser revisada por especialistas.</p></article>
                <article class="rounded-lg border border-amber-400 bg-amber-50 p-4 text-amber-950"><h3 class="font-bold">Determinación institucional pendiente</h3><p class="mt-2">Solo el MEP puede definir la ubicación y aprobación curricular; DGEV y COSEVI deben orientar la exactitud técnica y la cooperación.</p></article>
            </div>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Ruta de cierre</p><h2 class="text-2xl font-bold">Seis decisiones antes de solicitar un piloto</h2></div>
            <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['1', 'Revisión de seguridad vial', 'Dictaminar geometría, señales, trayectorias, tiempos y desenlaces por versión.'],
                    ['2', 'Determinación curricular', 'Definir con el MEP ubicación, edad, secuencia, evaluación y formación docente.'],
                    ['3', 'Cooperación institucional', 'Establecer con DGEV y COSEVI el mecanismo formal de revisión y acompañamiento.'],
                    ['4', 'Protección de datos', 'Aprobar base jurídica, minimización, autorizaciones, conservación, acceso e incidentes.'],
                    ['5', 'Accesibilidad comprobada', 'Probar tareas equivalentes con tecnologías de apoyo y personas usuarias.'],
                    ['6', 'Control de versiones', 'Registrar fuentes, dictámenes, cambios, responsables y vigencia antes de liberar contenido.'],
                ] as [$number, $title, $description])
                    <li class="rounded-xl border border-border bg-surface p-5"><span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $number }}</span><h3 class="mt-3 font-bold">{{ $title }}</h3><p class="mt-1 text-text-secondary">{{ $description }}</p></li>
                @endforeach
            </ol>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Fuentes consultadas</p><h2 class="text-2xl font-bold">Referencias oficiales y fecha de corte</h2><p class="text-text-secondary">Los enlaces se conservan para que una persona revisora pueda comprobar la fuente primaria. La matriz debe revisarse cuando cambie una norma o antes de cada presentación formal.</p></div>
            <ul class="grid gap-3 md:grid-cols-2">
                @foreach($sources as $source)
                    <li class="rounded-lg border border-border p-4"><a class="font-semibold text-primary underline" href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['label'] }}</a><p class="mt-1 text-sm text-text-secondary">{{ $source['institution'] }}</p></li>
                @endforeach
            </ul>
        </section>

        <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Límite de uso:</strong> esta matriz no constituye asesoría jurídica, cumplimiento certificado, aprobación curricular, aval del MEP, aval de COSEVI ni autorización para trabajar con estudiantes. Toda afirmación de ese tipo requiere evidencia emitida por la autoridad competente.</aside>
    </main>
</x-layouts.app>
