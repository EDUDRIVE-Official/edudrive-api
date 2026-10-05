<x-layouts.app title="EDUDRIVE — Mi pasaporte vial">
    <div class="passport-campus passport-sheet mx-auto flex max-w-4xl flex-col gap-6">
        <div class="campus-heading no-print flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-secondary">Tu historia de aprendizaje</p>
                <h1 class="mt-1 font-heading text-3xl font-bold">Mi Pasaporte Vial</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary">Una memoria viva de las habilidades, decisiones y prácticas que vas construyendo durante toda la vida.</p>
            </div>
            @if ($passport)
                <button type="button"
                    onclick="document.body.classList.add('printing-passport'); window.addEventListener('afterprint', () => document.body.classList.remove('printing-passport'), { once: true }); window.print();"
                    class="inline-flex min-h-11 items-center rounded-md border border-primary px-4 py-2 text-sm font-bold text-primary hover:bg-primary/10">
                    Imprimir o guardar como PDF
                </button>
            @endif
        </div>
        @if (session('status'))<div class="rounded-lg border border-success/30 bg-success/10 p-4 text-sm font-medium text-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm text-danger" role="alert"><p class="font-bold">Revisá tu reflexión:</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        @include('descubro.session-card')

        @if (! $passport)
            <x-ui.card>
                <div class="campus-empty-state">
                <img src="{{ asset('brand/mobility-campus.svg') }}" alt="" aria-hidden="true">
                <h2 class="font-heading text-xl font-bold">Tu Pasaporte Vial está pendiente de emisión</h2>
                <p class="font-sans text-sm text-text-secondary">
                    Todavía no tenés un pasaporte vial emitido. Se emite cuando una administradora u
                    administrador lo asigna.
                </p>
                <a href="{{ route('courses.index') }}" class="inline-flex min-h-11 items-center rounded-md bg-primary px-5 font-bold text-white">Explorar cursos →</a>
                </div>
            </x-ui.card>
        @else
            <section class="passport-print-only" aria-label="Identificación del Pasaporte Vial">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <img src="{{ asset('brand/edudrive-logo.svg') }}" alt="EDUDRIVE — Educación vial para la vida" class="h-14 w-auto">
                        <h1 class="mt-2 font-heading text-3xl font-bold text-text">Pasaporte Vial</h1>
                        <p class="mt-1 text-sm text-text-secondary">Titular: {{ auth()->user()->name }}</p>
                        <p class="mt-3 max-w-md text-xs leading-5 text-text-secondary">Escaneá el código para comprobar el estado actual. La consulta pública protege los datos personales.</p>
                    </div>
                    <img src="{{ $verificationQrDataUri }}" alt="Código QR para verificar este Pasaporte Vial" class="h-36 w-36 shrink-0 bg-white p-1">
                </div>
                <p class="mt-2 break-all text-xs text-text-secondary">Verificación pública: {{ $verificationUrl }}</p>
            </section>
            @php
                $statusLabels = ['active' => 'Activo', 'suspended' => 'Suspendido', 'revoked' => 'Revocado'];
                $historyLabels = ['status_changed' => 'Cambio de estado', 'level_changed' => 'Cambio de nivel'];
                $evidenceLabels = ['lesson_completed' => 'Lección completada', 'guided_practice_observed' => 'Práctica acompañada', 'self_reported_practice' => 'Práctica personal declarada', 'student_reflection' => 'Reflexión personal', 'course_completed' => 'Curso completado', 'exam_passed' => 'Examen aprobado'];
                $behaviorLabels = ['identified_risk' => 'Identificó un riesgo antes de actuar', 'made_safe_decision' => 'Tomó y explicó una decisión segura', 'applied_safe_sequence' => 'Aplicó la secuencia segura aprendida'];
                $practiceContextLabels = ['controlled_space' => 'Espacio controlado sin tránsito', 'tabletop_model' => 'Maqueta o representación', 'school_route' => 'Ruta escolar desde un punto protegido', 'neighborhood' => 'Barrio desde un punto protegido'];
                $selfAssessmentLabels = ['necesito_practicar' => 'Necesito practicar', 'voy_avanzando' => 'Voy avanzando', 'puedo_aplicarlo' => 'Puedo aplicarlo'];
                $reflectedSubjects = collect($passport['evidence'])->where('type', 'student_reflection')->pluck('details.observation_subject_id')->all();
                $selfPracticedLessonIds = collect($passport['evidence'])->where('type', 'self_reported_practice')->pluck('details.lesson_id')->all();
                $entryDiagnostics = collect($passport['evidence'])->filter(fn (array $item): bool => ($item['details']['evidence_kind'] ?? null) === 'course_entry_diagnostic')->keyBy('course_id');
                $transferChecks = collect($passport['evidence'])->filter(fn (array $item): bool => ($item['details']['evidence_kind'] ?? null) === 'course_transfer_check')->keyBy('course_id');
                $growthJourneys = $transferChecks->map(function (array $transfer, string $courseId) use ($entryDiagnostics): ?array {
                    $entry = $entryDiagnostics->get($courseId);
                    if (! is_array($entry)) return null;
                    $entryScore = (int) ($entry['details']['score'] ?? 0);
                    $transferScore = (int) ($transfer['details']['score'] ?? 0);
                    return [
                        'course_title' => $transfer['course_title'],
                        'entry' => $entryScore,
                        'entry_maximum' => (int) ($entry['details']['maximum_score'] ?? 3),
                        'transfer' => $transferScore,
                        'transfer_maximum' => (int) ($transfer['details']['maximum_score'] ?? 4),
                        'entry_percentage' => (int) round(($entryScore / max(1, (int) ($entry['details']['maximum_score'] ?? 3))) * 100),
                        'transfer_percentage' => (int) round(($transferScore / max(1, (int) ($transfer['details']['maximum_score'] ?? 4))) * 100),
                        'strengths' => $transfer['details']['strengths'] ?? [],
                        'practice_areas' => $transfer['details']['practice_areas'] ?? [],
                    ];
                })->filter();
                $skillMap = collect($passport['evidence'])
                    ->filter(fn (array $item): bool => ! empty($item['competency_title']))
                    ->groupBy('competency_title')
                    ->map(function ($items): array {
                        $latest = $items->max('occurred_at');
                        $types = $items->pluck('type')->unique()->values();
                        $isStale = $latest !== null && \Illuminate\Support\Carbon::parse($latest)->lt(now()->subYear());
                        $state = match (true) {
                            $isStale => ['label' => 'Por actualizar', 'style' => 'bg-warning/10 text-warning-text', 'reason' => 'La evidencia más reciente tiene más de un año. Una nueva experiencia ayudará a confirmar que la habilidad sigue vigente.'],
                            $types->count() >= 2 && $types->contains('guided_practice_observed') => ['label' => 'Evidencia diversa', 'style' => 'bg-success/10 text-success-text', 'reason' => 'Combina aprendizaje digital con una práctica observada en un entorno seguro.'],
                            $items->count() >= 3 => ['label' => 'En consolidación', 'style' => 'bg-primary/10 text-primary', 'reason' => 'Hay varias evidencias; una práctica observada o una experiencia diferente aumentará su diversidad.'],
                            default => ['label' => 'Evidencia inicial', 'style' => 'bg-safety/10 text-warning-text', 'reason' => 'La competencia comenzó a construirse. Necesita repetición y aplicación en otros contextos.'],
                        };

                        return [
                            'observations' => $items->count(),
                            'indicators' => $items->flatMap(fn (array $item): array => $item['indicator_labels'] ?? [])->unique()->values()->all(),
                            'latest' => $latest,
                            'state' => $state,
                        ];
                    });
                $evidenceMix = [
                    'digital' => collect($passport['evidence'])->whereIn('type', ['lesson_completed', 'exam_passed', 'course_completed'])->count(),
                    'practice' => collect($passport['evidence'])->where('type', 'guided_practice_observed')->count(),
                    'personal_practice' => collect($passport['evidence'])->where('type', 'self_reported_practice')->count(),
                    'reflection' => collect($passport['evidence'])->where('type', 'student_reflection')->count(),
                ];
                $evidenceFilterCounts = [
                    'all' => count($passport['evidence']),
                    'digital' => collect($passport['evidence'])->where('type', 'lesson_completed')->count(),
                    'practice' => collect($passport['evidence'])->whereIn('type', ['guided_practice_observed', 'self_reported_practice'])->count(),
                    'reflection' => collect($passport['evidence'])->where('type', 'student_reflection')->count(),
                    'achievement' => collect($passport['evidence'])->whereIn('type', ['course_completed', 'exam_passed'])->count(),
                ];
                $trustScore = (int) $passport['trust_score'];
                $trustState = match (true) {
                    $trustScore >= 75 => ['label' => 'Historial educativo acumulado', 'color' => 'bg-success'],
                    $trustScore >= 40 => ['label' => 'Trayectoria en consolidación', 'color' => 'bg-primary'],
                    $trustScore > 0 => ['label' => 'Trayectoria en construcción', 'color' => 'bg-warning'],
                    default => ['label' => 'Lista para comenzar', 'color' => 'bg-border'],
                };
                $nextPassportStep = match (true) {
                    $evidenceMix['digital'] === 0 => ['title' => 'Completá una experiencia digital', 'text' => 'Elegí una lección y resolvé sus decisiones para iniciar tu historial.'],
                    $evidenceMix['practice'] === 0 => ['title' => 'Llevá una habilidad a la práctica', 'text' => 'Realizá una práctica segura y pedí a tu persona acompañante que registre su observación.'],
                    $evidenceMix['reflection'] < $evidenceMix['practice'] => ['title' => 'Reflexioná sobre una práctica', 'text' => 'Contá qué aprendiste y qué harás la próxima vez para cerrar el ciclo.'],
                    default => ['title' => 'Sumá una experiencia diferente', 'text' => 'Continuá con una nueva competencia para ampliar la variedad de tu Pasaporte Vial.'],
                };
            @endphp

            <section class="campus-hero passport-identity relative overflow-hidden rounded-2xl bg-hero p-6 text-white shadow-lg sm:p-8" aria-labelledby="digital-passport-title">
                <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-secondary/55" aria-hidden="true"></div>
                <div class="absolute -bottom-28 right-32 h-56 w-56 rounded-full bg-accent/20" aria-hidden="true"></div>
                <div class="relative">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div>
                            <img src="{{ asset('brand/edudrive-logo-dark.svg') }}" alt="EDUDRIVE — Educación vial para la vida" class="h-14 w-auto">
                            <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-[#9ce8da]">Pasaporte Vial Digital</p>
                            <h2 id="digital-passport-title" class="mt-1 font-heading text-2xl font-bold">{{ auth()->user()->name }}</h2>
                            <p class="mt-2 text-sm text-white/75">Emitido el {{ $passport['issued_at_label'] }}</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="text-right">
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold">
                                    <span class="h-2 w-2 rounded-full {{ $passport['status'] === 'active' ? 'bg-accent' : ($passport['status'] === 'suspended' ? 'bg-accent' : 'bg-red-400') }}" aria-hidden="true"></span>
                                    {{ $statusLabels[$passport['status']] ?? $passport['status'] }}
                                </span>
                                <p class="mt-3 font-heading text-2xl font-bold"><span class="mr-1 align-middle font-sans text-xs font-medium text-white/60">Nivel</span> {{ $passport['level'] }}</p>
                            </div>
                            <img src="{{ $verificationQrDataUri }}" alt="Código QR de verificación del Pasaporte Vial" class="hidden h-24 w-24 rounded-lg bg-white p-1 sm:block">
                        </div>
                    </div>

                    <div class="mt-7 border-t border-white/20 pt-5">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-white/65">Confianza del recorrido</p><p class="mt-1 font-heading text-lg font-bold">{{ $trustState['label'] }}</p></div>
                            <p class="font-heading text-4xl font-bold">{{ $trustScore }}<span class="text-sm font-medium text-white/65">/100</span></p>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-white/20" role="progressbar" aria-label="Confianza del recorrido vial" aria-valuenow="{{ $trustScore }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full {{ $trustScore >= 75 ? 'bg-accent' : ($trustScore >= 40 ? 'bg-[#73b7ff]' : ($trustScore > 0 ? 'bg-accent' : 'bg-white/35')) }} transition-all" style="width: {{ $trustScore }}%"></div></div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-white/65">
                            <span>Folio educativo {{ strtoupper(substr($passport['id'], 0, 8)) }}</span>
                            <span>Documento educativo verificable · No sustituye una licencia</span>
                        </div>
                    </div>
                </div>
            </section>

            <details class="no-print rounded-xl border border-border bg-surface p-4 shadow-sm">
                <summary class="cursor-pointer text-sm font-bold text-primary">¿Qué significa la confianza del recorrido?</summary>
                <div class="mt-3 space-y-2 text-sm leading-6 text-text-secondary"><p>No es una nota, una prueba de dominio ni una habilitación para conducir. Resume actividad educativa registrada y su antigüedad; no mide la probabilidad de actuar con seguridad.</p><p>Puede aumentar con lecciones, cursos, prácticas observadas y reflexiones. Un puntaje alto no garantiza diversidad de situaciones ni transferencia a la vida cotidiana. Completar una práctica con retroalimentación no equivale a resolver una situación nueva sin ayuda.</p><p>Con el tiempo puede disminuir gradualmente para invitarte a actualizar habilidades. Los registros anteriores sin una clasificación explícita de evidencia no deben interpretarse como dominio demostrado.</p></div>
            </details>

            @if ($growthJourneys->isNotEmpty())
                <x-ui.card>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-success-text">Evolución de aprendizaje</p><h2 class="mt-1 font-heading text-lg font-bold text-text">Del punto de partida a la transferencia</h2></div>
                    <p class="mt-2 text-sm leading-6 text-text-secondary">Esta comparación muestra cómo cambió tu razonamiento dentro de un mismo curso. No compara tu resultado con otras personas.</p>
                    <div class="mt-4 grid gap-4">
                        @foreach ($growthJourneys as $journey)
                            <article class="rounded-lg border border-border bg-background p-4">
                                <h3 class="font-heading font-bold text-text">{{ $journey['course_title'] }}</h3>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div><div class="flex justify-between text-xs font-bold"><span>Punto de partida</span><span>{{ $journey['entry'] }}/{{ $journey['entry_maximum'] }}</span></div><div class="mt-2 h-3 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full bg-warning" style="width: {{ $journey['entry_percentage'] }}%"></div></div></div>
                                    <div><div class="flex justify-between text-xs font-bold"><span>Transferencia final</span><span>{{ $journey['transfer'] }}/{{ $journey['transfer_maximum'] }}</span></div><div class="mt-2 h-3 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full bg-success" style="width: {{ $journey['transfer_percentage'] }}%"></div></div></div>
                                </div>
                                @if ($journey['strengths'] !== [])<p class="mt-4 text-sm text-text-secondary"><strong class="text-success-text">Fortalezas actuales:</strong> {{ implode(' · ', $journey['strengths']) }}</p>@endif
                                @if ($journey['practice_areas'] !== [])<p class="mt-2 text-sm text-text-secondary"><strong class="text-warning-text">Próximo refuerzo:</strong> {{ implode(' · ', $journey['practice_areas']) }}</p>@else<p class="mt-2 text-sm font-medium text-success-text">Integraste todos los dominios en la misión final.</p>@endif
                            </article>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card class="no-print"
                x-data="{
                    copied: false,
                    canShare: typeof navigator.share === 'function',
                    async copyVerificationLink() {
                        try {
                            await navigator.clipboard.writeText(@js($verificationUrl));
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2500);
                        } catch (error) {
                            window.prompt('Copiá este enlace de verificación:', @js($verificationUrl));
                        }
                    },
                    async shareVerificationLink() {
                        try {
                            await navigator.share({
                                title: 'Verificación de Pasaporte Vial EDUDRIVE',
                                text: 'Consultá el estado de este recorrido de educación vial.',
                                url: @js($verificationUrl)
                            });
                        } catch (error) {
                            if (error.name !== 'AbortError') this.copyVerificationLink();
                        }
                    }
                }">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-md">
                        <p class="text-xs font-bold uppercase tracking-wide text-primary">Autenticidad y privacidad</p>
                        <h2 class="mt-1 font-heading text-lg font-bold">Compartí una verificación protegida</h2>
                        <p class="mt-2 text-sm leading-6 text-text-secondary">El enlace confirma vigencia, nivel y cantidades de evidencia. No muestra tu nombre, edad, correo, reflexiones ni observaciones.</p>
                    </div>
                    <span class="rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success-text">Código firmado</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" x-show="canShare" x-cloak @click="shareVerificationLink()" class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary">Compartir desde mi dispositivo</button>
                    <button type="button" @click="copyVerificationLink()" class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary">Copiar enlace de verificación</button>
                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-md border border-border px-4 py-2 text-sm font-bold text-text hover:border-primary">Abrir verificación pública</a>
                </div>
                <p class="mt-2 text-sm font-medium text-success" x-show="copied" x-cloak role="status" aria-live="polite">Enlace copiado.</p>
                <details class="mt-4 rounded-lg border border-border bg-background p-3">
                    <summary class="cursor-pointer text-sm font-bold text-primary">Mostrar código QR en pantalla</summary>
                    <div class="mt-3 flex flex-col items-center gap-2 text-center">
                        <img src="{{ $verificationQrDataUri }}" alt="Código QR en pantalla para verificar este Pasaporte Vial" class="h-52 w-52 bg-white p-2">
                        <p class="max-w-sm text-xs leading-5 text-text-secondary">Escanealo con otro dispositivo. El QR contiene el mismo enlace anónimo del botón de verificación.</p>
                    </div>
                </details>
                <details class="mt-3 rounded-lg border border-border bg-background p-3">
                    <summary class="cursor-pointer text-sm font-bold text-text">¿Compartiste el enlace por error?</summary>
                    <p class="mt-2 text-xs leading-5 text-text-secondary">Podés crear un enlace y QR nuevos. Los anteriores dejarán de verificar este pasaporte inmediatamente.</p>
                    <form method="POST" action="{{ route('road-passport.verification.rotate') }}" class="mt-3" onsubmit="return confirm('¿Crear un enlace nuevo e invalidar todos los enlaces y QR anteriores?')">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-md border border-danger px-4 py-2 text-sm font-bold text-danger-text hover:bg-danger/10">Renovar enlace de verificación</button>
                    </form>
                </details>
            </x-ui.card>

            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Perfil vivo</p><h2 class="mt-1 font-heading text-lg font-bold">Mi mapa de habilidades viales</h2></div>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $skillMap->count() }} competencia{{ $skillMap->count() === 1 ? '' : 's' }}</span>
                </div>
                <p class="mt-2 text-sm leading-6 text-text-secondary">Este mapa crece con experiencias digitales, prácticas observadas y reflexiones. No compara tu recorrido con el de otras personas.</p>

                @if ($skillMap->isNotEmpty())
                    <div class="mt-4 grid gap-3">
                        @foreach ($skillMap as $competencyTitle => $skill)
                            <article class="rounded-lg border border-border bg-background p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-heading font-bold text-text">{{ $competencyTitle }}</h3>
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $skill['state']['style'] }}">{{ $skill['state']['label'] }}</span><span class="text-xs font-medium text-text-secondary">{{ $skill['observations'] }} evidencia{{ $skill['observations'] === 1 ? '' : 's' }}</span></div>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-text-secondary">{{ $skill['state']['reason'] }}</p>
                                @if ($skill['indicators'] !== [])
                                    <ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                        @foreach ($skill['indicators'] as $indicator)
                                            <li class="flex gap-2"><span class="text-success" aria-hidden="true">✓</span><span>{{ $indicator }}</span></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-2 text-sm text-text-secondary">La evidencia está registrada; las habilidades específicas aparecerán con nuevas experiencias.</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-dashed border-border bg-background p-4 text-sm text-text-secondary">Completá tu primera lección para comenzar a construir el mapa.</div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-2 border-t border-border pt-4 text-center sm:grid-cols-4">
                    <div class="rounded-lg bg-background p-3"><p class="font-heading text-2xl font-bold text-primary">{{ $evidenceMix['digital'] }}</p><p class="mt-1 text-xs text-text-secondary">Evidencias digitales</p></div>
                    <div class="rounded-lg bg-background p-3"><p class="font-heading text-2xl font-bold text-primary">{{ $evidenceMix['practice'] }}</p><p class="mt-1 text-xs text-text-secondary">Prácticas observadas</p></div>
                    <div class="rounded-lg bg-background p-3"><p class="font-heading text-2xl font-bold text-primary">{{ $evidenceMix['personal_practice'] }}</p><p class="mt-1 text-xs text-text-secondary">Prácticas declaradas</p></div>
                    <div class="rounded-lg bg-background p-3"><p class="font-heading text-2xl font-bold text-primary">{{ $evidenceMix['reflection'] }}</p><p class="mt-1 text-xs text-text-secondary">Reflexiones</p></div>
                </div>
                <aside class="no-print mt-4 rounded-lg border border-primary/25 bg-primary/5 p-4" aria-label="Siguiente paso recomendado">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Siguiente paso recomendado</p>
                    <p class="mt-1 font-heading font-bold text-text">{{ $nextPassportStep['title'] }}</p>
                    <p class="mt-1 text-sm leading-6 text-text-secondary">{{ $nextPassportStep['text'] }}</p>
                    @if ($evidenceMix['digital'] === 0 || ($evidenceMix['practice'] > 0 && $evidenceMix['reflection'] >= $evidenceMix['practice']))
                        <a href="{{ route('courses.index') }}" class="mt-3 inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary">Ver mis opciones de aprendizaje</a>
                    @endif
                </aside>
            </x-ui.card>

            <x-ui.card>
                <h2 class="font-heading text-lg font-bold">Historial</h2>
                @if (count($passport['history']) > 0)
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th scope="col" class="px-4 py-2">Cambio</th>
                                <th scope="col" class="px-4 py-2">De</th>
                                <th scope="col" class="px-4 py-2">A</th>
                                <th scope="col" class="px-4 py-2">Fecha</th>
                                <th scope="col" class="px-4 py-2">Motivo</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($passport['history'] as $entry)
                            <tr>
                                <td class="px-4 py-2">{{ $historyLabels[$entry['type']] ?? $entry['type'] }}</td>
                                <td class="px-4 py-2">{{ $entry['from'] }}</td>
                                <td class="px-4 py-2">{{ $entry['to'] }}</td>
                                <td class="px-4 py-2">{{ $entry['occurred_at_label'] }}</td>
                                <td class="px-4 py-2">{{ $entry['reason'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @else
                    <p class="font-sans text-sm text-text-secondary">Todavía no hay cambios registrados.</p>
                @endif
            </x-ui.card>

            <x-ui.card x-data="{ evidenceFilter: 'all' }">
                <h2 class="font-heading text-lg font-bold">Evidencia</h2>
                @if (count($passport['evidence']) > 0)
                    <div class="no-print my-4 flex flex-wrap gap-2" role="group" aria-label="Filtrar evidencia del Pasaporte Vial">
                        @foreach ([
                            'all' => 'Todo',
                            'digital' => 'Aprendizaje digital',
                            'practice' => 'Prácticas',
                            'reflection' => 'Reflexiones',
                            'achievement' => 'Logros',
                        ] as $filterValue => $filterLabel)
                            <button type="button"
                                @click="evidenceFilter = '{{ $filterValue }}'"
                                :aria-pressed="evidenceFilter === '{{ $filterValue }}'"
                                :class="evidenceFilter === '{{ $filterValue }}' ? 'bg-primary text-white' : 'border border-border bg-background text-text hover:border-primary'"
                                class="min-h-10 rounded-full px-3 py-2 text-xs font-bold transition-colors">
                                {{ $filterLabel }} <span aria-hidden="true">({{ $evidenceFilterCounts[$filterValue] }})</span>
                            </button>
                        @endforeach
                    </div>
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th scope="col" class="px-4 py-2">Tipo</th>
                                <th scope="col" class="px-4 py-2">Curso</th>
                                <th scope="col" class="px-4 py-2">Aprendizaje demostrado</th>
                                <th scope="col" class="px-4 py-2">Fecha</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($passport['evidence'] as $item)
                            @php
                                $filterCategory = match ($item['type']) {
                                    'lesson_completed' => 'digital',
                                    'guided_practice_observed', 'self_reported_practice' => 'practice',
                                    'student_reflection' => 'reflection',
                                    'course_completed', 'exam_passed' => 'achievement',
                                    default => 'digital',
                                };
                                $isTransferCheck = ($item['details']['evidence_kind'] ?? null) === 'course_transfer_check';
                            @endphp
                            <tr @if ($item['type'] === 'guided_practice_observed') id="practice-reflection-{{ $item['subject_id'] }}" @endif x-show="evidenceFilter === 'all' || evidenceFilter === '{{ $filterCategory }}'" class="scroll-mt-6">
                                <td class="px-4 py-2">{{ $isTransferCheck ? 'Evaluación de transferencia' : ($evidenceLabels[$item['type']] ?? $item['type']) }}</td>
                                <td class="px-4 py-2 font-medium text-text">{{ $item['course_title'] }}</td>
                                <td class="px-4 py-2">
                                    <span class="font-medium text-text">{{ $item['details']['lesson_title'] ?? 'Resultado verificado' }}</span>
                                    @if ($item['competency_title'])
                                        <span class="mt-1 block text-xs font-medium text-primary">{{ $item['competency_title'] }}</span>
                                    @endif
                                    @if (! empty($item['details']['learning_design_version']))
                                        <span class="mt-1 block text-xs text-text-secondary">Versión pedagógica {{ $item['details']['learning_design_version'] }} · {{ collect($item['details']['jurisdictions'] ?? [])->map(fn ($context) => ['CR' => 'Costa Rica', 'GLOBAL' => 'Aplicación universal'][$context] ?? $context)->implode(' + ') }}</span>
                                    @endif
                                    @if (! empty($item['indicator_labels']))
                                        <ul class="mt-1 space-y-1 text-xs text-text-secondary">
                                            @foreach ($item['indicator_labels'] as $indicator)
                                                <li>✓ {{ $indicator }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if (! empty($item['details']['behavior']))
                                        <span class="mt-1 block text-xs text-text-secondary">{{ $behaviorLabels[$item['details']['behavior']] ?? $item['details']['behavior'] }}</span>
                                    @endif
                                    @if (! empty($item['details']['practice_context']))
                                        <span class="mt-1 block text-xs text-text-secondary"><strong>Entorno de práctica:</strong> {{ $practiceContextLabels[$item['details']['practice_context']] ?? 'Entorno seguro confirmado' }}</span>
                                    @endif
                                    @if (! empty($item['details']['observation']))
                                        <span class="mt-1 block text-xs italic text-text-secondary">“{{ $item['details']['observation'] }}”</span>
                                    @endif
                                    @if ($item['type'] === 'self_reported_practice')
                                        <span class="mt-2 inline-flex rounded-full bg-safety/10 px-2 py-1 text-xs font-bold text-warning-text">Declarada por vos · sin verificación externa</span>
                                    @elseif ($item['type'] === 'guided_practice_observed')
                                        <span class="mt-2 inline-flex rounded-full bg-success/10 px-2 py-1 text-xs font-bold text-success">Validada por {{ $item['observer_name'] ?? 'una persona acompañante autorizada' }}</span>
                                    @endif
                                    @if (! empty($item['details']['self_assessment']))
                                        <span class="mt-2 block text-xs text-text-secondary"><strong>Autoevaluación:</strong> {{ $selfAssessmentLabels[$item['details']['self_assessment']] ?? 'Registrada' }}</span>
                                    @endif
                                    @if (! empty($item['details']['reflection']))
                                        <span class="mt-1 block text-xs italic text-text-secondary">“{{ $item['details']['reflection'] }}”</span>
                                    @endif
                                    @if ($isTransferCheck)
                                        <div class="mt-2 rounded-lg border border-primary/25 bg-primary/5 p-3">
                                            <p class="font-heading text-lg font-bold text-primary">{{ $item['details']['score'] ?? 0 }} de {{ $item['details']['maximum_score'] ?? 4 }} competencias integradas</p>
                                            @if (! empty($item['details']['strengths']))
                                                <p class="mt-2 text-xs text-text-secondary"><strong class="text-success-text">Fortalezas:</strong> {{ implode(' · ', $item['details']['strengths']) }}</p>
                                            @endif
                                            @if (! empty($item['details']['practice_areas']))
                                                <p class="mt-1 text-xs text-text-secondary"><strong class="text-warning-text">Para reforzar:</strong> {{ implode(' · ', $item['details']['practice_areas']) }}</p>
                                            @else
                                                <p class="mt-1 text-xs font-medium text-success-text">Transferencia completa en este intento.</p>
                                            @endif
                                            <span class="mt-2 inline-flex rounded-full bg-surface px-2 py-1 text-[11px] font-bold text-text-secondary">Resultado formativo · no modifica el certificado</span>
                                        </div>
                                    @elseif ($item['type'] === 'student_reflection')
                                        <span class="mt-1 block text-xs text-text-secondary"><strong>Aprendí:</strong> {{ $item['details']['learned'] ?? 'Reflexión registrada' }}</span>
                                        <span class="mt-1 block text-xs text-text-secondary"><strong>La próxima vez:</strong> {{ $item['details']['next_action'] ?? 'Continuar practicando' }}</span>
                                    @elseif ($item['type'] === 'guided_practice_observed' && ! in_array($item['subject_id'], $reflectedSubjects, true))
                                        <details class="no-print mt-3 rounded-lg border border-border p-3" @if ($selectedObservationSubjectId === $item['subject_id']) open data-selected-observation="true" @endif>
                                            <summary class="cursor-pointer text-xs font-bold text-primary">Responder con mi reflexión</summary>
                                            <form method="POST" action="{{ route('road-passport.reflections.store') }}" class="mt-3 grid gap-3">
                                                @csrf
                                                <input type="hidden" name="observation_subject_id" value="{{ $item['subject_id'] }}">
                                                <label class="grid gap-1 text-xs font-medium">¿Qué aprendiste?<textarea name="learned" required minlength="10" maxlength="500" rows="2" class="rounded-lg border border-border bg-background px-3 py-2"></textarea></label>
                                                <label class="grid gap-1 text-xs font-medium">¿Qué harás la próxima vez?<textarea name="next_action" required minlength="10" maxlength="500" rows="2" class="rounded-lg border border-border bg-background px-3 py-2"></textarea></label>
                                                <button class="w-fit rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Guardar mi reflexión</button>
                                            </form>
                                        </details>
                                    @elseif ($item['type'] === 'guided_practice_observed')
                                        <span class="mt-2 block text-xs font-medium text-success">✓ Reflexión completada</span>
                                    @elseif ($item['type'] === 'lesson_completed' && ! in_array($item['subject_id'], $selfPracticedLessonIds, true))
                                        @if ($canSelfReportPractice)
                                            <details class="no-print mt-3 rounded-lg border border-border p-3">
                                                <summary class="cursor-pointer text-xs font-bold text-primary">Registrar una práctica personal</summary>
                                                <form method="POST" action="{{ route('road-passport.personal-practices.store') }}" class="mt-3 grid gap-3">
                                                    @csrf
                                                    <input type="hidden" name="lesson_id" value="{{ $item['subject_id'] }}">
                                                    <select name="behavior" required class="rounded-lg border border-border bg-background px-3 py-2 text-xs"><option value="identified_risk">Identifiqué un riesgo</option><option value="made_safe_decision">Tomé y expliqué una decisión segura</option><option value="applied_safe_sequence">Apliqué la secuencia aprendida</option></select>
                                                    <select name="practice_context" required class="rounded-lg border border-border bg-background px-3 py-2 text-xs"><option value="controlled_space">Espacio controlado sin tránsito</option><option value="tabletop_model">Maqueta o representación</option><option value="school_route">Ruta escolar desde un punto protegido</option><option value="neighborhood">Barrio desde un punto protegido</option></select>
                                                    <textarea name="note" required minlength="10" maxlength="500" rows="2" class="rounded-lg border border-border bg-background px-3 py-2 text-xs" placeholder="¿Qué ocurrió y qué aprendiste?"></textarea>
                                                    <label class="flex items-start gap-2 text-xs text-text-secondary"><input type="checkbox" name="safe_environment" value="1" required class="mt-0.5">Confirmo que estaba en un lugar seguro y que registré esto antes o después de moverme.</label>
                                                    <button class="w-fit rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Guardar como práctica declarada</button>
                                                </form>
                                            </details>
                                        @elseif ($selfPracticeRestriction === 'birth_date_required')
                                            <p class="mt-3 rounded-lg border border-safety/30 bg-safety/10 p-3 text-xs text-text-secondary">Para saber qué modalidad de práctica es segura para vos, primero <a href="{{ route('student-profile.show') }}" class="font-bold text-primary underline">completá tu fecha de nacimiento</a>.</p>
                                        @else
                                            <p class="mt-3 rounded-lg border border-safety/30 bg-safety/10 p-3 text-xs text-text-secondary">Esta práctica debe realizarse en un entorno seguro y ser registrada por una persona adulta acompañante. Pedile ayuda a tu familia o responsable.</p>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $item['occurred_at_label'] }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                    @foreach ([
                        'digital' => 'Todavía no hay aprendizaje digital registrado.',
                        'practice' => 'Todavía no hay prácticas registradas.',
                        'reflection' => 'Todavía no hay reflexiones registradas.',
                        'achievement' => 'Todavía no hay logros registrados.',
                    ] as $filterValue => $emptyMessage)
                        @if ($evidenceFilterCounts[$filterValue] === 0)
                            <p x-show="evidenceFilter === '{{ $filterValue }}'" x-cloak class="mt-4 rounded-lg border border-dashed border-border bg-background p-4 text-sm text-text-secondary">{{ $emptyMessage }}</p>
                        @endif
                    @endforeach
                @else
                    <p class="font-sans text-sm text-text-secondary">Todavía no hay evidencia registrada.</p>
                @endif
            </x-ui.card>
            <footer class="passport-print-only border-t border-border pt-4 text-xs leading-5 text-text-secondary">
                <p><strong>Importante:</strong> este documento resume un proceso educativo. No sustituye una licencia de conducir, una certificación legal ni una autorización para circular.</p>
                <p class="mt-1 break-all"><strong>Código de verificación:</strong> {{ $verificationCode }}</p>
                <p class="mt-1">Generado el {{ now()->timezone(config('app.timezone'))->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y') }}.</p>
            </footer>
        @endif
    </div>
</x-layouts.app>
