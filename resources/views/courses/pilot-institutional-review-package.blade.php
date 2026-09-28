<x-layouts.app title="EDUDRIVE — Paquete de revisión institucional P912">
    @php
        $completeControls = collect(['workspace', 'reviewers', 'reviews', 'candidates'])->filter(fn ($key) => $readiness[$key]['complete'])->count();
    @endphp

    <main id="pilot-main" class="mx-auto max-w-6xl space-y-8">
        <header class="space-y-3 border-b border-border pb-6">
            <p class="text-sm font-semibold text-primary">Anexo H · P912-PILOT-01 · {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Paquete de solicitud de revisión institucional</h1>
            <p class="max-w-4xl text-text-secondary">Borrador preparado el {{ $preparedOn }} para solicitar criterio técnico sobre la propuesta EduDrive para primaria de 9–12 años. No ha sido enviado y no contiene nombres, firmas, números de oficio ni decisiones institucionales.</p>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" @click="window.print()" class="min-h-11 rounded bg-primary px-4 font-semibold text-white">Imprimir paquete</button><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.institutional-submission') }}">Abrir expediente consolidado</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.normative-alignment') }}">Ver matriz normativa</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.pilot-dossier') }}">Volver al expediente</a></div>
        </header>

        <aside class="rounded-xl border border-amber-400 bg-amber-50 p-5 text-amber-950">
            <h2 class="text-xl font-bold">Estado antes de remitir</h2>
            <p class="mt-2"><strong>{{ $completeControls }} de 4 controles internos completos.</strong> Este paquete puede utilizarse para solicitar orientación preliminar, pero no para pedir autorización de aplicación con estudiantes mientras existan controles internos pendientes.</p>
        </aside>

        <section class="space-y-5 rounded-2xl border border-border bg-surface p-6 print:border-0 print:p-0">
            <div class="border-b border-border pb-4"><p class="text-sm font-semibold uppercase tracking-wide text-primary">Documento 1</p><h2 class="text-2xl font-bold">Modelo de oficio de presentación</h2></div>
            <div class="space-y-4 leading-relaxed">
                <p><strong>Fecha:</strong> ____________________ &nbsp;&nbsp; <strong>Oficio:</strong> ____________________</p>
                <p><strong>Señoras y señores</strong><br>Institución o dependencia: __________________________________________<br>Atención: __________________________________________</p>
                <p><strong>Asunto: Solicitud de revisión técnica preliminar del piloto EduDrive P912</strong></p>
                <p>Por este medio se presenta para revisión la propuesta preliminar “Piloto de educación vial para primaria de 9–12 años”, identificada como P912-PILOT-01. La propuesta busca que niñas y niños practiquen decisiones como peatones y pasajeros mediante situaciones visuales, acompañamiento adulto, comprobación separada y actividades de transferencia en espacios protegidos.</p>
                <p>La documentación adjunta describe objetivos, arquitectura pedagógica, prototipos visuales, controles de revisión, protocolo de ensayo y una matriz preliminar de referencias oficiales. La presentación no solicita todavía aprobación nacional ni autorización para trabajar con estudiantes. Su finalidad es obtener observaciones técnicas verificables para corregir alcance, contenido, representación vial, accesibilidad, protección y ubicación curricular.</p>
                <p>Se solicita, dentro del ámbito de competencia de esa institución, indicar: a) aspectos conformes o aprovechables; b) cambios indispensables; c) información adicional necesaria; d) dependencias que deban participar; y e) condiciones mínimas para considerar una fase posterior de evaluación controlada.</p>
                <p>La respuesta será registrada como criterio institucional y no se interpretará como aval distinto del que expresamente conste en el documento emitido.</p>
                <div class="grid gap-6 pt-6 md:grid-cols-2"><p class="border-t border-text pt-2">Nombre y cargo de la persona remitente</p><p class="border-t border-text pt-2">Firma y medio oficial de contacto</p></div>
            </div>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Documento 2</p><h2 class="text-2xl font-bold">Distribución sugerida por competencia</h2></div>
            <div class="overflow-x-auto rounded-xl border border-border"><table class="w-full min-w-[850px] border-collapse bg-surface text-left"><thead><tr class="bg-background"><th class="p-4">Instancia o perfil</th><th class="p-4">Criterio solicitado</th><th class="p-4">Material principal</th><th class="p-4">No se presume</th></tr></thead><tbody>
                @foreach([
                    ['MEP · instancia curricular competente', 'Edad, objetivos, secuencia, mediación, evaluación, formación docente y ubicación curricular.', 'Expediente, unidad modelo y matriz normativa.', 'Que la propuesta ya forma parte del currículo oficial.'],
                    ['DGEV y COSEVI · instancia técnica competente', 'Exactitud vial, roles, señales, infraestructura, trayectorias, prevención y alcance de cooperación.', 'Recorrido visual, guía especializada y matriz normativa.', 'Aval técnico, patrocinio o condición de campaña oficial.'],
                    ['Asesoría jurídica y protección de datos', 'Base habilitante, autorizaciones, minimización, conservación, acceso, incidentes e imagen de menores.', 'Protocolo, instrumentos y matriz normativa.', 'Que los formularios actuales bastan para un piloto real.'],
                    ['Accesibilidad y población usuaria', 'Equivalencia, tecnologías de apoyo, carga cognitiva, lenguaje, interacción y ajustes razonables.', 'Escenas, alternativas textuales, guía y protocolo.', 'Cumplimiento comprobado por existir texto alternativo.'],
                ] as [$instance, $criterion, $materials, $limit])<tr class="border-t border-border"><th class="p-4 align-top">{{ $instance }}</th><td class="p-4 align-top">{{ $criterion }}</td><td class="p-4 align-top">{{ $materials }}</td><td class="p-4 align-top">{{ $limit }}</td></tr>@endforeach
            </tbody></table></div>
        </section>

        <section class="space-y-4 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950">
            <div><p class="text-sm font-semibold uppercase tracking-wide">Documento 3</p><h2 class="text-2xl font-bold">Instrucciones comunes para revisión</h2></div>
            <ol class="grid gap-4 md:grid-cols-2">
                @foreach([
                    ['1', 'Identificar la versión', 'Registrar P912-PILOT-01, la versión visual y la fecha exacta examinada.'],
                    ['2', 'Delimitar la competencia', 'Aclarar qué dimensión se revisa y qué aspectos quedan fuera del criterio emitido.'],
                    ['3', 'Citar evidencia', 'Relacionar cada observación con una escena, objetivo, documento o referencia verificable.'],
                    ['4', 'Clasificar el hallazgo', 'Distinguir corrección obligatoria, recomendación, consulta o aspecto aceptable.'],
                    ['5', 'Evitar aprobación implícita', 'No interpretar silencio, participación o revisión parcial como aval general.'],
                    ['6', 'Cerrar con siguiente acción', 'Indicar cambio, responsable sugerido y evidencia necesaria para volver a revisar.'],
                ] as [$number, $title, $description])<li class="rounded-lg bg-white/80 p-4"><span class="font-bold">{{ $number }}. {{ $title }}</span><p class="mt-1">{{ $description }}</p></li>@endforeach
            </ol>
        </section>

        <section class="space-y-5 rounded-2xl border border-border bg-surface p-6 print:break-before-page print:border-0 print:p-0">
            <div class="border-b border-border pb-4"><p class="text-sm font-semibold uppercase tracking-wide text-primary">Documento 4</p><h2 class="text-2xl font-bold">Formulario de observaciones institucionales</h2><p class="mt-2 text-text-secondary">Plantilla para completar fuera de EduDrive. Esta página no envía ni almacena la información escrita.</p></div>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach(['Institución o dependencia', 'Nombre y cargo de quien emite el criterio', 'Área de competencia', 'Documento o versión revisada', 'Fecha de revisión', 'Referencia oficial del criterio u oficio'] as $label)<label class="space-y-2 font-semibold"><span>{{ $label }}</span><input class="min-h-12 w-full rounded border border-border bg-background px-3" type="text"></label>@endforeach
            </div>
            <fieldset class="space-y-3"><legend class="font-bold">Alcance del criterio</legend><div class="grid gap-3 md:grid-cols-2">@foreach(['Curricular y pedagógico', 'Seguridad vial', 'Accesibilidad', 'Protección y datos', 'Otro ámbito declarado'] as $option)<label class="flex min-h-11 items-center gap-3 rounded border border-border p-3"><input type="checkbox"> {{ $option }}</label>@endforeach</div></fieldset>
            @foreach(['Aspectos conformes o aprovechables', 'Correcciones indispensables y su fundamento', 'Recomendaciones no obligatorias', 'Información o evidencia adicional requerida', 'Dependencias adicionales que deberían participar'] as $label)<label class="block space-y-2 font-semibold"><span>{{ $label }}</span><textarea class="min-h-28 w-full rounded border border-border bg-background p-3"></textarea></label>@endforeach
            <fieldset class="space-y-3"><legend class="font-bold">Resultado dentro del ámbito revisado</legend><div class="grid gap-3">@foreach(['Requiere cambios antes de una nueva revisión', 'Puede avanzar a revisión coordinada con otras instancias', 'No corresponde a la competencia de esta instancia', 'Criterio favorable limitado al alcance expresamente indicado'] as $option)<label class="flex min-h-11 items-center gap-3 rounded border border-border p-3"><input name="institutional_result" type="radio"> {{ $option }}</label>@endforeach</div></fieldset>
            <div class="grid gap-6 pt-8 md:grid-cols-2"><p class="border-t border-text pt-2">Firma o validación institucional</p><p class="border-t border-text pt-2">Fecha y medio de verificación</p></div>
        </section>

        <section class="space-y-4 rounded-2xl border border-border bg-surface p-6">
            <h2 class="text-2xl font-bold">Lista de anexos que deben acompañar el envío</h2>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach(['A. Unidad modelo y matriz de objetivos', 'B. Recorrido de escenas visuales', 'C. Guía para especialistas', 'D. Cobertura y hallazgos', 'E. Protocolo controlado', 'F. Instrumentos operativos', 'G. Matriz normativa y curricular', 'H. Este paquete de revisión', 'I. Incorporación de personas revisoras'] as $annex)<label class="flex min-h-11 items-center gap-3 rounded border border-border p-3"><input type="checkbox"> {{ $annex }}</label>@endforeach
            </div>
        </section>

        <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Control de envío:</strong> antes de remitir, una persona responsable debe completar destinatario, remitente, número de oficio, versión, anexos y canal oficial. EduDrive no envía este paquete, no registra respuestas externas y no convierte una revisión en autorización automática.</aside>
    </main>
</x-layouts.app>
