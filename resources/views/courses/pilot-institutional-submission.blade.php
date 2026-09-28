<x-layouts.app title="EDUDRIVE — Expediente consolidado P912">
    <style>
        @media print {
            body { background: #fff !important; color: #111827 !important; }
            body > header { display: none !important; }
            .institutional-print,
            .institutional-print * { color: #111827 !important; box-shadow: none !important; }
            .institutional-print section,
            .institutional-print article,
            .institutional-print aside,
            .institutional-print table,
            .institutional-print tr,
            .institutional-print input,
            .institutional-print textarea { background: #fff !important; border-color: #94a3b8 !important; }
            .institutional-print a { text-decoration: none !important; }
            .institutional-print article,
            .institutional-print aside,
            .institutional-print tr { break-inside: avoid; }
        }
    </style>
    @php
        $controls = [
            ['Curso borrador aislado', $readiness['workspace']['complete'], $readiness['workspace']['lessons'].' de 4 lecciones'],
            ['Especialidades designadas', $readiness['reviewers']['complete'], count($readiness['reviewers']['active_specialties']).' de 3'],
            ['Escenas con cobertura completa', $readiness['reviews']['complete'], $readiness['reviews']['completed_scenes'].' de '.$readiness['reviews']['total_scenes']],
            ['Candidaturas vinculadas', $readiness['candidates']['complete'], $readiness['candidates']['linked_scenes'].' de '.$readiness['candidates']['total_scenes']],
        ];
        $completeControls = collect($controls)->filter(fn ($control) => $control[1])->count();
        $annexes = [
            ['A', 'Unidad modelo y matriz de objetivos', 'pilot-instruments.unit', 'Objetivos, prácticas, fases y criterios observables.'],
            ['B', 'Recorrido de nueve situaciones', 'pilot-instruments.visual-sequence', 'Secuencia de ensayo visual y toma de decisiones.'],
            ['C', 'Guía para especialistas', 'pilot-instruments.visual-review-guide', 'Criterios comunes para revisión docente, vial y accesible.'],
            ['D', 'Cobertura y hallazgos', 'pilot-instruments.visual-review-summary', 'Estado de revisión por escena y especialidad.'],
            ['E', 'Protocolo controlado', 'pilot-instruments.pilot-protocol', 'Puertas de entrada, suspensión, aplicación y decisión.'],
            ['F', 'Instrumentos operativos', 'pilot-instruments.pilot-forms', 'Listas y formularios sin almacenamiento.'],
            ['G', 'Matriz normativa y curricular', 'pilot-instruments.normative-alignment', 'Fuentes oficiales, correspondencias y brechas.'],
            ['H', 'Paquete de revisión institucional', 'pilot-instruments.institutional-review-package', 'Oficio, distribución, instrucciones y formulario.'],
            ['I', 'Incorporación de personas revisoras', 'pilot-instruments.reviewer-onboarding', 'Perfiles, invitación, independencia, aceptación y verificación.'],
        ];
    @endphp

    <main id="pilot-main" class="institutional-print mx-auto max-w-6xl space-y-10 print:max-w-none print:text-black">
        <section class="flex min-h-[70vh] flex-col justify-between rounded-2xl border border-border bg-surface p-8 print:min-h-[95vh] print:border-0 print:p-0">
            <div class="space-y-8">
                <div class="flex items-start justify-between gap-6"><div><p class="text-sm font-bold uppercase tracking-[.2em] text-primary">EduDrive</p><p class="mt-1 text-sm text-text-secondary">Educación vial basada en decisiones</p></div><span class="rounded-full border border-border px-4 py-2 text-sm font-semibold">Borrador para revisión</span></div>
                <div class="max-w-4xl space-y-4 pt-16"><p class="font-semibold text-primary">EXPEDIENTE P912-PILOT-01</p><h1 class="text-4xl font-bold md:text-6xl">Piloto de educación vial para primaria de 9–12 años</h1><p class="text-xl text-text-secondary">Expediente consolidado para orientación curricular, técnica, jurídica y de accesibilidad.</p></div>
            </div>
            <div class="grid gap-5 border-t border-border pt-6 md:grid-cols-3"><div><p class="text-sm text-text-secondary">Versión visual</p><p class="font-bold">{{ $sceneVersion }}</p></div><div><p class="text-sm text-text-secondary">Preparado</p><p class="font-bold">{{ $preparedOn }}</p></div><div><p class="text-sm text-text-secondary">Estado interno</p><p class="font-bold">{{ $completeControls }} de 4 controles</p></div></div>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" @click="window.print()" class="min-h-11 rounded bg-primary px-4 font-semibold text-white">Imprimir expediente consolidado</button><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.institutional-review-package') }}">Paquete de revisión</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.pilot-dossier') }}">Expediente navegable</a></div>
        </section>

        <section class="space-y-5 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">1. Control documental</p><h2 class="text-3xl font-bold">Identificación y límites</h2></div>
            <div class="overflow-x-auto rounded-xl border border-border"><table class="w-full min-w-[720px] border-collapse bg-surface text-left"><tbody>
                @foreach([
                    ['Título', 'Piloto de educación vial para primaria de 9–12 años'],
                    ['Código', 'P912-PILOT-01'],
                    ['Población inicial', 'Niñas y niños de 9–12 años, con acompañamiento adulto'],
                    ['Roles priorizados', 'Peatón y pasajero'],
                    ['Estado', 'Propuesta preliminar para revisión; no autorizada para aplicación con estudiantes'],
                    ['Finalidad de esta versión', 'Solicitar criterio técnico y definir correcciones, responsables y condiciones de una fase posterior'],
                    ['Exclusiones', 'No acredita eficacia, aprobación curricular, cumplimiento certificado, aval institucional ni despliegue nacional'],
                ] as [$label, $value])<tr class="border-t border-border first:border-t-0"><th class="w-1/3 p-4 align-top">{{ $label }}</th><td class="p-4">{{ $value }}</td></tr>@endforeach
            </tbody></table></div>
        </section>

        <section class="space-y-5 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">2. Resumen ejecutivo</p><h2 class="text-3xl font-bold">Propuesta y necesidad</h2></div>
            <div class="grid gap-5 lg:grid-cols-[1.4fr_.6fr]">
                <article class="space-y-4 rounded-2xl border border-border bg-surface p-6"><p>EduDrive propone un entorno digital para practicar decisiones viales antes de enfrentarlas en la vida real. La unidad inicial enseña a reconocer un espacio protegido, identificar información faltante, detener una decisión, comprobar movimientos y pedir ayuda o cambiar de plan.</p><p>La propuesta separa diagnóstico, práctica con devolución, transferencia protegida, comprobación y seguimiento. No enseña que una señal sustituya la observación ni traslada a la niñez la responsabilidad de las personas conductoras, la infraestructura o las instituciones.</p><p>El expediente solicita revisión externa para corregir el producto antes de considerar cualquier piloto. La validación debe ser multidisciplinaria, versionada y expresamente limitada al ámbito de cada persona o institución revisora.</p></article>
                <aside class="rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950"><h3 class="text-xl font-bold">Decisión solicitada</h3><p class="mt-3">Indicar aspectos aprovechables, correcciones indispensables, evidencia adicional, instancias participantes y condiciones mínimas para una eventual fase controlada.</p><p class="mt-4 font-semibold">No se solicita todavía aprobación nacional.</p></aside>
            </div>
        </section>

        <section class="space-y-5 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">3. Resultados previstos</p><h2 class="text-3xl font-bold">Objetivos observables del piloto</h2></div>
            <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['1', 'Espacio protegido', 'Señalar un lugar de espera dentro de la acera y separado del borde.'],
                    ['2', 'Visibilidad', 'Reconocer qué oculta la vista y qué información todavía no puede comprobarse.'],
                    ['3', 'Pausa', 'Posponer el avance ante un conflicto o información insuficiente.'],
                    ['4', 'Comprobación', 'Considerar movimientos que pueden llegar al cruce, incluidos los giros.'],
                    ['5', 'Ayuda y cambio de plan', 'Explicar cómo pedir acompañamiento o elegir una alternativa protegida.'],
                ] as [$number, $title, $description])<li class="rounded-xl border border-border bg-surface p-5"><span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $number }}</span><h3 class="mt-3 font-bold">{{ $title }}</h3><p class="mt-1 text-text-secondary">{{ $description }}</p></li>@endforeach
            </ol>
        </section>

        <section class="space-y-5 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">4. Estado comprobable</p><h2 class="text-3xl font-bold">Preparación interna al momento de exportar</h2></div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($controls as [$title, $complete, $detail])<article class="rounded-xl border {{ $complete ? 'border-green-400 bg-green-50 text-green-950' : 'border-amber-400 bg-amber-50 text-amber-950' }} p-5"><p class="text-sm font-semibold">{{ $complete ? 'Completo internamente' : 'Pendiente' }}</p><h3 class="mt-1 text-xl font-bold">{{ $title }}</h3><p class="mt-2">{{ $detail }}</p></article>@endforeach
            </div>
            <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Puerta de seguridad:</strong> completar los cuatro controles internos no equivale a autorización externa. Mientras falte cualquiera de ellos, este expediente solo debe emplearse para orientación preliminar y corrección.</aside>
        </section>

        <section class="space-y-5 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">5. Síntesis normativa</p><h2 class="text-3xl font-bold">Correspondencias y brechas principales</h2><p class="mt-2 text-text-secondary">Fuentes verificadas el {{ $normativeVerifiedOn }}. La matriz completa se incorpora como anexo G.</p></div>
            <div class="overflow-x-auto rounded-xl border border-border"><table class="w-full min-w-[900px] border-collapse bg-surface text-left"><thead><tr class="bg-background"><th class="p-4">Referencia</th><th class="p-4">Lectura</th><th class="p-4">Decisión pendiente</th></tr></thead><tbody>
                @foreach($normativeRows as $row)<tr class="border-t border-border"><th class="p-4 align-top">{{ $row['source'] }}</th><td class="p-4 align-top"><span class="font-semibold">{{ $row['status'] }}.</span> {{ $row['evidence'] }}</td><td class="p-4 align-top">{{ $row['gap'] }}</td></tr>@endforeach
            </tbody></table></div>
            <p class="text-sm text-text-secondary">La lista completa de {{ count($normativeSources) }} fuentes oficiales, sus enlaces y el control temporal de la reforma del artículo 217 constan en el anexo G.</p>
        </section>

        <section class="space-y-5 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">6. Registro de anexos</p><h2 class="text-3xl font-bold">Contenido que integra el expediente</h2></div>
            <div class="space-y-3">
                @foreach($annexes as [$letter, $title, $route, $description])<article class="flex gap-4 rounded-xl border border-border bg-surface p-4"><span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary font-bold text-white">{{ $letter }}</span><div class="grow"><h3 class="font-bold"><a class="underline print:no-underline" href="{{ route($route) }}">{{ $title }}</a></h3><p class="mt-1 text-text-secondary">{{ $description }}</p></div><label class="flex items-start gap-2 print:flex"><input class="mt-1" type="checkbox"><span class="text-sm">Adjunto</span></label></article>@endforeach
            </div>
            <p class="rounded-lg border border-amber-400 bg-amber-50 p-4 text-amber-950"><strong>Importante:</strong> imprimir esta página no incorpora automáticamente el contenido completo de los anexos A–I. La persona responsable debe exportar cada anexo, comprobar su versión y marcarlo como adjunto antes del envío.</p>
        </section>

        <section class="space-y-6 print:break-before-page">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">7. Hoja de despacho</p><h2 class="text-3xl font-bold">Control previo a la remisión oficial</h2></div>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach(['Institución destinataria', 'Dependencia y persona de contacto verificadas', 'Número de oficio', 'Canal oficial de entrega', 'Fecha de envío', 'Persona responsable del expediente', 'Versión final remitida', 'Referencia de recibido'] as $label)<label class="space-y-2 font-semibold"><span>{{ $label }}</span><input class="min-h-12 w-full rounded border border-border bg-background px-3" type="text"></label>@endforeach
            </div>
            <fieldset class="space-y-3"><legend class="font-bold">Comprobaciones obligatorias</legend><div class="grid gap-3 md:grid-cols-2">@foreach(['Destinatario y competencia confirmados', 'Anexos A–I exportados y versionados', 'Fuentes y vigencia normativa revisadas', 'Datos personales innecesarios eliminados', 'Firmas y autorizaciones internas completas', 'Copia íntegra conservada en el registro institucional'] as $check)<label class="flex min-h-11 items-center gap-3 rounded border border-border p-3"><input type="checkbox"> {{ $check }}</label>@endforeach</div></fieldset>
            <div class="grid gap-8 pt-12 md:grid-cols-2"><p class="border-t border-text pt-2">Responsable de preparación</p><p class="border-t border-text pt-2">Responsable que autoriza la remisión</p></div>
        </section>

        <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Declaración final:</strong> este documento organiza una solicitud de revisión. No contiene resultados con estudiantes, dictámenes externos, aprobación curricular, cumplimiento certificado, aval del MEP o COSEVI ni autorización para implementar el programa.</aside>
    </main>
</x-layouts.app>
