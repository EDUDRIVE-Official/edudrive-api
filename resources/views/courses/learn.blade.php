<x-layouts.app :title="'EDUDRIVE — '.$course['title']">
    <div class="learning-campus flex flex-col gap-6">
        @php
            $adminPreview = $adminPreview ?? false;
            $nextLessonId = null;
            $moduleStats = [];
            $totalMinutes = 0;
            $completedMinutes = 0;
            foreach ($course['modules'] as $courseModule) {
                $moduleLessonIds = collect($courseModule['units'])
                    ->flatMap(fn (array $courseUnit) => collect($lessonsByUnit[$courseUnit['id']] ?? [])->pluck('id'))
                    ->values();
                $moduleCompleted = $moduleLessonIds->filter(fn (string $id): bool => in_array($id, $progress['completed_lessons'], true))->count();
                $moduleUnlocked = collect($courseModule['units'])->contains(fn (array $courseUnit): bool => ($unlockByUnit[$courseUnit['id']]['unlocked'] ?? false) === true);
                foreach ($courseModule['units'] as $courseUnit) {
                    foreach ($lessonsByUnit[$courseUnit['id']] ?? [] as $timedLesson) {
                        $lessonMinutes = (int) ($timedLesson['duration_minutes'] ?? 0);
                        $totalMinutes += $lessonMinutes;
                        if (in_array($timedLesson['id'], $progress['completed_lessons'], true)) $completedMinutes += $lessonMinutes;
                    }
                }
                $moduleStats[$courseModule['id']] = [
                    'total' => $moduleLessonIds->count(),
                    'completed' => $moduleCompleted,
                    'percentage' => $moduleLessonIds->isEmpty() ? 0 : (int) round(($moduleCompleted / $moduleLessonIds->count()) * 100),
                    'unlocked' => $moduleUnlocked,
                ];
                if ($nextLessonId === null && $moduleUnlocked) {
                    foreach ($courseModule['units'] as $courseUnit) {
                        if (($unlockByUnit[$courseUnit['id']]['unlocked'] ?? false) !== true) continue;
                        foreach ($lessonsByUnit[$courseUnit['id']] ?? [] as $candidateLesson) {
                            if (! in_array($candidateLesson['id'], $progress['completed_lessons'], true)) {
                                $nextLessonId = $candidateLesson['id'];
                                break 2;
                            }
                        }
                    }
                }
            }
        @endphp
        <div>
            <a href="{{ route('courses.show', $course['id']) }}" class="ed-enlace"><x-ui.icon name="back" size="sm" />Volver al curso</a>
            <section class="campus-hero mt-3 px-6 py-7 text-white sm:px-8" aria-labelledby="learning-course-title">
                <div class="relative flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p class="text-lg font-bold text-accent">{{ $adminPreview ? 'Vista integral de superadministración' : 'Tu aula' }} · {{ $course['code'] }}</p>
                        <h1 id="learning-course-title" class="mt-2 max-w-3xl">{{ $course['title'] }}</h1>
                    </div>
                    <div class="rounded-lg bg-white/10 px-4 py-3 text-right"><p class="text-sm font-bold text-white">{{ $adminPreview ? $progress['total_lessons'].' lecciones disponibles' : $progress['completed_lessons_count'].' de '.$progress['total_lessons'].' lecciones' }}</p><p class="mt-1 text-xs text-white/75">{{ $adminPreview ? 'Sin bloqueos ni requisitos previos' : 'Aproximadamente '.intdiv(max(0, $totalMinutes - $completedMinutes) + 59, 60).' h restantes' }}</p></div>
                </div>
                @if ($adminPreview)
                    <p class="relative mt-5 rounded-lg border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white/90">Esta revisión no crea una matrícula, no registra respuestas y no modifica el progreso de ningún estudiante.</p>
                @else
                    <div class="relative mt-5 h-3 overflow-hidden rounded-full bg-white/20" role="progressbar" aria-valuenow="{{ $progress['progress_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-accent transition-all" style="width: {{ $progress['progress_percentage'] }}%"></div>
                    </div>
                    <p class="relative mt-2 text-right text-sm font-semibold text-white/85">{{ $progress['progress_percentage'] }}% completado</p>
                @endif
            </section>
        </div>

        <nav class="grid gap-3 md:grid-cols-2" aria-label="Avance por módulo">
            @foreach ($course['modules'] as $moduleOverview)
                @php $moduleStat = $moduleStats[$moduleOverview['id']]; @endphp
                <a href="#module-{{ $moduleOverview['id'] }}" class="rounded-lg border p-4 transition-colors hover:bg-background focus-visible:outline-none focus-visible:shadow-focus {{ $moduleStat['unlocked'] ? 'border-border bg-surface' : 'border-border bg-background opacity-70' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Módulo {{ $moduleOverview['position'] }}</p><p class="mt-1 font-heading font-bold text-text">{{ $moduleOverview['title'] }}</p></div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $moduleStat['percentage'] === 100 ? 'bg-success/10 text-success-text' : ($moduleStat['unlocked'] ? 'bg-primary/10 text-primary' : 'bg-border text-text-secondary') }}">
                            {{ $adminPreview ? 'Disponible' : ($moduleStat['percentage'] === 100 ? 'Completado' : ($moduleStat['unlocked'] ? $moduleStat['percentage'].'%' : 'Bloqueado')) }}
                        </span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full {{ $moduleStat['percentage'] === 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $moduleStat['percentage'] }}%"></div></div>
                    <p class="mt-2 text-xs text-text-secondary">{{ $adminPreview ? $moduleStat['total'].' lecciones desbloqueadas' : $moduleStat['completed'].' de '.$moduleStat['total'].' lecciones' }}</p>
                </a>
            @endforeach
        </nav>

        @if (array_sum($selfAssessmentSummary) > 0)
            <section class="rounded-lg border border-border bg-surface p-5" aria-labelledby="self-assessment-summary-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Mi percepción del aprendizaje</p><h2 id="self-assessment-summary-title" class="mt-1 font-heading text-lg font-bold text-text">Cómo me siento para aplicar lo aprendido</h2></div>
                    <a href="{{ route('learning-events.show', $enrollment->id()->value()) }}" class="text-sm font-bold text-primary hover:text-secondary">Ver mi bitácora →</a>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-safety/10 p-3"><p class="font-heading text-2xl font-bold text-warning-text">{{ $selfAssessmentSummary['necesito_practicar'] }}</p><p class="mt-1 text-xs text-text-secondary">Quiero practicar más</p></div>
                    <div class="rounded-lg bg-primary/10 p-3"><p class="font-heading text-2xl font-bold text-primary">{{ $selfAssessmentSummary['voy_avanzando'] }}</p><p class="mt-1 text-xs text-text-secondary">Voy avanzando</p></div>
                    <div class="rounded-lg bg-success/10 p-3"><p class="font-heading text-2xl font-bold text-success-text">{{ $selfAssessmentSummary['puedo_aplicarlo'] }}</p><p class="mt-1 text-xs text-text-secondary">Siento que puedo aplicarlo</p></div>
                </div>
                <p class="mt-3 text-xs leading-5 text-text-secondary">Estas respuestas no son una calificación. Sirven para recomendar práctica y observar cómo cambia tu confianza con nuevas experiencias.</p>
            </section>
        @endif

        @if (! $adminPreview && $enrollment->status() === \Modules\Academic\Domain\Enums\EnrollmentStatus::Completed && $progress['progress_percentage'] < 100)
            <aside class="rounded-lg border border-primary/40 bg-primary/10 p-4" aria-label="Curso ampliado">
                <p class="font-heading font-bold text-text">Este curso tiene contenido nuevo</p>
                <p class="mt-2 text-sm leading-6 text-text-secondary">
                    Tu finalización anterior y tu certificado se conservan. Podés completar las nuevas lecciones para ampliar las evidencias de tu Pasaporte Vial.
                </p>
            </aside>
        @endif

        @if ($nextLessonId)
            <section class="rounded-xl border border-primary/30 bg-surface p-5 shadow-sm" aria-labelledby="next-learning-step-title">
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-primary">{{ $adminPreview ? 'Acceso completo' : 'Tu siguiente paso' }}</p>
                        <h2 id="next-learning-step-title" class="mt-1 font-heading text-xl font-bold text-text">{{ $adminPreview ? 'Todas las lecciones están disponibles para revisión' : 'Continuá desde la primera lección pendiente' }}</h2>
                        <p class="mt-2 text-sm leading-6 text-text-secondary">Recorré cada experiencia en tres momentos: comprendé la idea, practicá una decisión y explicá cómo la aplicarías.</p>
                    </div>
                    <a href="#lesson-{{ $nextLessonId }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md bg-primary px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus">{{ $adminPreview ? 'Ir a la primera lección →' : 'Ir a mi siguiente reto →' }}</a>
                </div>
                <ol class="mt-5 grid gap-3 border-t border-border pt-4 text-sm sm:grid-cols-3" aria-label="Cómo completar una lección">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span><span><strong class="block text-text">Descubrí</strong><span class="text-text-secondary">Leé, escuchá y explorá.</span></span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-secondary text-xs font-bold text-white">2</span><span><strong class="block text-text">Decidí</strong><span class="text-text-secondary">Resolvé situaciones reales.</span></span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#f5b700] text-xs font-bold text-[#102a43]">3</span><span><strong class="block text-text">Reflexioná</strong><span class="text-text-secondary">Registrá qué aplicarías.</span></span></li>
                </ol>
            </section>
        @endif

        @if ($reinforcement)
            <aside class="rounded-lg border border-safety/50 bg-safety/10 p-4" aria-label="Refuerzo recomendado">
                <p class="text-xs font-bold uppercase tracking-wide text-warning-text">Refuerzo recomendado para vos</p>
                <h2 class="mt-1 font-heading text-lg font-bold text-text">Volvé a explorar: {{ $reinforcement['lesson_title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-text-secondary">{{ $reinforcement['reason'] }}</p>
                <a href="#lesson-{{ $reinforcement['lesson_id'] }}" class="mt-3 inline-flex min-h-11 items-center rounded-md border border-warning px-4 py-2 text-sm font-bold text-warning-text hover:bg-safety/10 focus-visible:outline-none focus-visible:shadow-focus">Repasar esta lección</a>
            </aside>
        @endif

        <aside class="rounded-lg border border-primary/40 bg-primary/10 p-4" aria-label="Orientación personalizada">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-heading font-bold text-text">{{ $learnerStage['identity'] }}</span>
                <span class="rounded-full bg-surface px-3 py-1 text-xs font-medium text-text-secondary">{{ $learnerStage['age_range'] }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-text-secondary">{{ $learnerStage['guidance'] }}</p>
        </aside>

        @if (! $adminPreview && $course['code'] === 'EDU-EXP-001' && $progress['completed_lessons_count'] === 0)
            @include('courses.blocks.course-entry-diagnostic', ['learnerStage' => $learnerStage, 'enrollmentId' => $enrollment->id()->value(), 'entryDiagnosticRecorded' => $entryDiagnosticRecorded])
        @endif

        @if (session('status'))
            <p class="rounded-md border border-success bg-surface px-4 py-3 text-sm text-success">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="rounded-md border border-danger-text bg-surface px-4 py-3 text-sm text-danger-text">{{ session('error') }}</p>
        @endif
        @if ($errors->any())
            <div class="rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">
                <p class="font-bold">Revisá el cierre de la lección:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($progress['progress_percentage'] === 100)
            <section id="mission-complete" class="scroll-mt-6 rounded-lg border-2 border-success bg-surface p-6 text-center shadow-sm target:ring-4 target:ring-success/20">
                <p class="text-4xl" aria-hidden="true">🏁</p>
                <h2 class="mt-2 font-heading text-2xl font-bold text-success-text">¡Misión cumplida!</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-text-secondary">
                    Completaste todas las lecciones. Tus decisiones y los indicadores demostrados ya forman parte de tu historial vial.
                </p>
                <div class="mt-4 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('road-passport.show') }}" class="inline-flex min-h-[44px] items-center rounded-md bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus">
                        Ver mi Pasaporte Vial
                    </a>
                    <a href="{{ route('certificates.index') }}" class="inline-flex min-h-[44px] items-center rounded-md border border-primary px-5 py-2 text-sm font-semibold text-primary hover:bg-background focus-visible:outline-none focus-visible:shadow-focus">
                        Ver mi certificado
                    </a>
                    <a href="{{ route('gamification.dashboard') }}" class="inline-flex min-h-[44px] items-center rounded-md border border-primary px-5 py-2 text-sm font-semibold text-primary hover:bg-background focus-visible:outline-none focus-visible:shadow-focus">
                        Ver recompensa y XP
                    </a>
                </div>
            </section>
            @if ($course['code'] === 'EDU-EXP-001')
                @include('courses.blocks.safe-crossing-transfer-check', ['enrollmentId' => $enrollment->id()->value(), 'transferCheckRecorded' => $transferCheckRecorded])
            @endif
        @endif

        @foreach ($course['modules'] as $module)
            @php $currentModuleStat = $moduleStats[$module['id']]; @endphp
            <section id="module-{{ $module['id'] }}" class="scroll-mt-6 overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
                <div class="border-b border-border bg-background px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-text-secondary">Módulo {{ $module['position'] }}</p><h2 class="mt-1 font-heading text-xl font-bold">{{ $module['title'] }}</h2></div>
                        <span class="rounded-full bg-surface px-3 py-1 text-xs font-bold text-text-secondary">{{ $currentModuleStat['completed'] }} / {{ $currentModuleStat['total'] }}</span>
                    </div>
                    @unless ($currentModuleStat['unlocked'])
                        <p class="mt-3 rounded-md border border-warning/30 bg-warning/10 px-3 py-2 text-sm text-warning-text">🔒 Completá el módulo anterior para abrir esta misión.</p>
                    @endunless
                    @include('courses.blocks.module-scene', ['module' => $module])
                </div>

                <div class="flex flex-col divide-y divide-border">
                    @foreach ($module['units'] as $unit)
                        @php
                            $unitUnlocked = $unlockByUnit[$unit['id']]['unlocked'] ?? false;
                            $unitLessons = $lessonsByUnit[$unit['id']] ?? [];
                            $unitCompletedCount = collect($unitLessons)->filter(fn (array $unitLesson): bool => in_array($unitLesson['id'], $progress['completed_lessons'], true))->count();
                        @endphp
                        <article class="px-5 py-5 {{ $unitUnlocked ? '' : 'opacity-60' }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium text-primary">Unidad {{ $unit['position'] }}</p>
                                    <h3 class="mt-1 font-heading text-lg font-semibold">{{ $unit['title'] }}</h3>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ ! $unitUnlocked ? 'bg-background text-text-secondary' : ($unitCompletedCount === count($unitLessons) && count($unitLessons) > 0 ? 'bg-success/10 text-success-text' : 'bg-primary/10 text-primary') }}">
                                    @if ($adminPreview) Desbloqueada @elseif (! $unitUnlocked) 🔒 Bloqueada @elseif ($unitCompletedCount === count($unitLessons) && count($unitLessons) > 0) ✓ Unidad completada @else {{ $unitCompletedCount }} de {{ count($unitLessons) }} lecciones @endif
                                </span>
                            </div>

                            <div class="mt-4 flex flex-col gap-4">
                                @foreach ($unitLessons as $lesson)
                                    @php
                                        $completed = in_array($lesson['id'], $progress['completed_lessons'], true);
                                    @endphp
                                    @php
                                        $design = $lesson['learning_design'] ?? null;
                                        $stageLabels = [
                                            'pending_review' => 'Etapa pendiente de revisión curricular',
                                            'explore' => 'Explorar', 'discover' => 'Descubrir', 'understand' => 'Comprender',
                                            'prepare' => 'Prepararse', 'drive' => 'Conducir', 'perfect' => 'Perfeccionar',
                                            'refresh' => 'Actualizar', 'teach' => 'Enseñar',
                                        ];
                                    @endphp
                                    <details
                                        id="lesson-{{ $lesson['id'] }}"
                                        class="group/lesson scroll-mt-6 overflow-hidden rounded-xl border {{ $completed ? 'border-success/35 bg-success/5' : 'border-border bg-background' }} shadow-xs target:border-primary target:ring-4 target:ring-primary/20"
                                        x-data="{
                                            lessonPage: {{ $errors->any() ? count($lesson['blocks']) + 1 : 0 }},
                                            lastLessonPage: {{ count($lesson['blocks']) + 1 }},
                                            changeLessonPage(step) {
                                                this.stopReading();
                                                this.lessonPage = Math.max(0, Math.min(this.lastLessonPage, step));
                                                this.$nextTick(() => { this.$refs.pageHeading.focus(); this.$refs.pageHeading.scrollIntoView({block: 'start'}); window.dispatchEvent(new Event('resize')); });
                                            },
                                            speaking: false,
                                            supported: typeof window !== 'undefined' && 'speechSynthesis' in window,
                                            readLesson() {
                                                if (! this.supported) return;
                                                window.speechSynthesis.cancel();
                                                const parts = Array.from(this.$el.querySelectorAll('[data-read-aloud]')).filter(element => element.getClientRects().length).map(element => element.innerText.trim()).filter(Boolean);
                                                const voice = new SpeechSynthesisUtterance(parts.join('. '));
                                                voice.lang = 'es-CR';
                                                voice.rate = 0.92;
                                                voice.onend = () => this.speaking = false;
                                                voice.onerror = () => this.speaking = false;
                                                this.speaking = true;
                                                window.speechSynthesis.speak(voice);
                                            },
                                            stopReading() {
                                                if (this.supported) window.speechSynthesis.cancel();
                                                this.speaking = false;
                                            },
                                            printPractice() {
                                                this.$refs.practiceSheet.classList.add('print-target');
                                                document.body.classList.add('printing-practice');
                                                const cleanup = () => {
                                                    this.$refs.practiceSheet.classList.remove('print-target');
                                                    document.body.classList.remove('printing-practice');
                                                };
                                                window.addEventListener('afterprint', cleanup, { once: true });
                                                window.print();
                                                setTimeout(cleanup, 1500);
                                            }
                                        }"
                                        @toggle="if (! $el.open) stopReading()"
                                        @if ($unitUnlocked && $lesson['id'] === $nextLessonId) open @endif
                                    >
                                        <summary class="flex min-h-16 cursor-pointer list-none items-center gap-4 px-4 py-3 marker:hidden hover:bg-primary/5 focus-visible:outline-none focus-visible:shadow-focus">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $completed ? 'bg-success text-white' : ($lesson['id'] === $nextLessonId ? 'bg-primary text-white' : 'border-2 border-border bg-surface text-text-secondary') }} font-bold" aria-hidden="true">{{ $completed ? '✓' : $loop->iteration }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-heading font-bold text-text">{{ $lesson['title'] }}</span>
                                                <span class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-text-secondary">
                                                    <span>{{ $adminPreview ? 'Lección desbloqueada' : ($completed ? 'Aprendizaje registrado' : ($lesson['id'] === $nextLessonId ? 'Siguiente reto' : 'Lección pendiente')) }}</span>
                                                    @if ($lesson['duration_minutes'])<span>⏱ {{ $lesson['duration_minutes'] }} min</span>@endif
                                                </span>
                                            </span>
                                            <span class="text-xl text-primary transition-transform group-open/lesson:rotate-180" aria-hidden="true">⌄</span>
                                        </summary>
                                        @if ($unitUnlocked)
                                            <div class="border-t border-border px-4 pb-5 sm:px-5">
                                            @include('courses.blocks.lesson-page-navigation', ['navigationPosition' => 'top'])
                                            <div class="mt-4 flex flex-wrap items-center gap-2" x-show="supported" x-cloak>
                                                <button type="button" x-show="! speaking" @click="readLesson()" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-primary px-4 py-2 text-sm font-bold text-primary hover:bg-primary/5 focus-visible:outline-none focus-visible:shadow-focus" aria-label="Escuchar esta lección en voz alta">🔊 Escuchar lección</button>
                                                <button type="button" x-show="speaking" @click="stopReading()" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-warning px-4 py-2 text-sm font-bold text-warning-text hover:bg-safety/10 focus-visible:outline-none focus-visible:shadow-focus">■ Detener lectura</button>
                                                <span class="text-xs text-text-secondary" aria-live="polite" x-text="speaking ? 'Leyendo en español…' : 'Lectura en voz alta disponible'"></span>
                                            </div>
                                            <div x-show="lessonPage === 0">
                                            <div data-read-aloud class="mt-4 grid gap-3 rounded-md border border-border bg-surface p-6 sm:grid-cols-[1fr_auto]">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Tu misión</p>
                                                    <p class="mt-3 text-xl font-medium leading-relaxed text-text">
                                                        {{ $design['behavior_objective'] ?? $lesson['summary'] ?? 'Completar esta experiencia y aplicar lo aprendido.' }}
                                                    </p>
                                                </div>
                                                <div class="flex flex-wrap items-start gap-2 sm:justify-end">
                                                    @if ($lesson['duration_minutes'])
                                                        <span class="rounded-full bg-background px-3 py-1 text-xs font-medium text-text-secondary">⏱ {{ $lesson['duration_minutes'] }} min</span>
                                                    @endif
                                                    @if ($design)
                                                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ $stageLabels[$design['stage']] ?? ucfirst($design['stage']) }}</span>
                                                    @endif
                                                </div>
                                                @if (($design['requires_guardian'] ?? false) === true && $learnerStage['requires_guardian'])
                                                    @if ($hasGuardian)
                                                        <div class="rounded-md border border-primary/25 bg-primary/5 p-3 text-xs leading-5 sm:col-span-2">
                                                            <p class="font-bold text-primary">Cómo funciona el acompañamiento</p>
                                                            @if ($completed)
                                                                <p class="mt-1 text-text-secondary">La parte digital ya está completa y la práctica debe aparecer en la cuenta de tu acompañante. Su registro aporta evidencia al Pasaporte Vial, pero no bloquea el siguiente reto.</p>
                                                            @else
                                                                <p class="mt-1 text-text-secondary">Primero completá esta lección digital. Después aparecerá automáticamente una práctica pendiente en la cuenta de tu acompañante. Su registro aporta evidencia al Pasaporte Vial, pero no bloquea el siguiente reto.</p>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="rounded-md border border-warning/40 bg-safety/10 p-3 text-xs leading-5 sm:col-span-2">
                                                            <p class="font-bold text-warning-text">Acompañamiento todavía no vinculado</p>
                                                            <p class="mt-1 text-text-secondary">Podés completar ahora la parte digital y avanzar. La actividad fuera de la pantalla debe esperar hasta que una persona adulta esté vinculada y pueda supervisarla.</p>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                            <aside data-read-aloud class="mt-4 rounded-lg border border-primary/25 bg-primary/5 p-4" aria-label="Adaptación de esta lección">
                                                <p class="text-xs font-bold uppercase tracking-wide text-primary">Para vos: {{ $learnerStage['identity'] }}</p>
                                                <dl class="mt-3 grid gap-3 text-sm md:grid-cols-3">
                                                    <div><dt class="font-bold text-text">Cómo recorrerla</dt><dd class="mt-1 leading-5 text-text-secondary">{{ $learnerStage['instruction'] }}</dd></div>
                                                    <div><dt class="font-bold text-text">Pregunta para pensar</dt><dd class="mt-1 leading-5 text-text-secondary">{{ $learnerStage['reflection_prompt'] }}</dd></div>
                                                    <div><dt class="font-bold text-text">Práctica recomendada</dt><dd class="mt-1 leading-5 text-text-secondary">{{ $learnerStage['practice_mode'] }}</dd></div>
                                                </dl>
                                            </aside>
                                            @if ($course['code'] === 'EDU-EXP-001')
                                                @include('courses.blocks.age-adapted-mission', ['learnerStage' => $learnerStage, 'design' => $design, 'lesson' => $lesson])
                                            @endif
                                            <details class="mt-5 rounded-lg border border-border p-5">
                                            <summary class="cursor-pointer text-lg font-semibold">Apoyos, práctica y fuentes de esta lección</summary>
                                            <section x-ref="practiceSheet" data-read-aloud class="practice-sheet mt-4 rounded-lg border border-success/30 bg-success/5 p-4" aria-label="Ficha de práctica segura">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div><p class="text-xs font-bold uppercase tracking-wide text-success-text">Ficha de práctica segura</p><h4 class="mt-1 font-heading text-lg font-bold text-text">Llevá esta misión a una situación cotidiana</h4></div>
                                                    <button type="button" @click="printPractice()" class="no-print inline-flex min-h-11 items-center rounded-md border border-success px-4 py-2 text-sm font-bold text-success-text hover:bg-success/10 focus-visible:outline-none focus-visible:shadow-focus">Imprimir ficha</button>
                                                </div>
                                                <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                                                    <div><dt class="font-bold text-text">Objetivo</dt><dd class="mt-1 leading-6 text-text-secondary">{{ $design['behavior_objective'] ?? $lesson['summary'] }}</dd></div>
                                                    <div><dt class="font-bold text-text">Lugar y modalidad</dt><dd class="mt-1 leading-6 text-text-secondary">{{ $learnerStage['practice_mode'] }}</dd></div>
                                                </dl>
                                                <div class="mt-4" x-data="{ checked: [] }">
                                                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-text-secondary">Lista de verificación</p>
                                                    <div class="grid gap-2 text-sm text-text-secondary sm:grid-cols-2">
                                                        @foreach ([
                                                            'preparar' => ['1. Prepará', 'Elegí un momento sin prisa y un punto protegido para observar.'],
                                                            'detectar' => ['2. Detectá', 'Nombrá al menos un peligro y una condición que podría cambiar.'],
                                                            'decidir' => ['3. Decidí', 'Explicá qué acción deja más tiempo, espacio y visibilidad.'],
                                                            'conversar' => ['4. Conversá', 'Compará la decisión con lo aprendido y pensá una mejora.'],
                                                        ] as $checkValue => [$checkTitle, $checkText])
                                                            <label class="flex cursor-pointer items-start gap-3 rounded-md bg-surface p-3">
                                                                <input type="checkbox" value="{{ $checkValue }}" x-model="checked" class="mt-1 rounded border-border text-success focus:ring-success">
                                                                <span><strong class="block text-text">{{ $checkTitle }}</strong>{{ $checkText }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <p class="mt-3 text-xs font-medium text-text-secondary" aria-live="polite"><span x-text="checked.length"></span> de 4 pasos revisados. Esta lista funciona solamente en tu dispositivo y no se guarda.</p>
                                                </div>
                                                @if (($design['requires_guardian'] ?? false) === true && $learnerStage['requires_guardian'])
                                                    <p class="mt-4 rounded-md border border-warning/40 bg-safety/10 p-3 text-sm font-bold text-warning-text">⚠ Esta práctica debe realizarse con una persona adulta y sin ingresar a la calzada ni exponerse al tránsito.</p>
                                                @else
                                                    <p class="mt-4 rounded-md border border-primary/20 bg-primary/5 p-3 text-sm text-text-secondary">Realizá la observación desde un lugar permitido y protegido. Si las condiciones no son seguras, cambiá de sitio o posponé la práctica.</p>
                                                @endif
                                                <p class="mt-3 text-xs text-text-secondary">Completá la lista antes o después de la observación, nunca mientras cruzás, pedaleás o conducís. Al terminar, podés pedir a tu persona acompañante que registre lo observado. La ficha no sustituye supervisión ni normas locales.</p>
                                                @if ($course['code'] === 'EDU-EXP-001')
                                                    @include('courses.blocks.guardian-observation-rubric', ['learnerStage' => $learnerStage, 'lesson' => $lesson, 'design' => $design])
                                                @endif
                                            </section>
                                            @if ($design)
                                                @php
                                                    $jurisdictionLabels = ['CR' => 'Costa Rica', 'GLOBAL' => 'Aplicación universal'];
                                                    $sourceLabels = [
                                                        'pgrweb.go.cr' => 'Sistema Costarricense de Información Jurídica',
                                                        'www.csv.go.cr' => 'Consejo de Seguridad Vial de Costa Rica',
                                                        'csv.go.cr' => 'Consejo de Seguridad Vial de Costa Rica',
                                                        'www.mep.go.cr' => 'Ministerio de Educación Pública de Costa Rica',
                                                        'mep.go.cr' => 'Ministerio de Educación Pública de Costa Rica',
                                                        'www.who.int' => 'Organización Mundial de la Salud',
                                                        'who.int' => 'Organización Mundial de la Salud',
                                                    ];
                                                @endphp
                                                <details class="mt-4 rounded-lg border border-border bg-surface p-4">
                                                    <summary class="cursor-pointer text-sm font-bold text-primary">Contexto y fuentes de esta lección</summary>
                                                    <div class="mt-3 grid gap-4 text-sm md:grid-cols-2">
                                                        <div>
                                                            <p class="font-bold text-text">Dónde aplica</p>
                                                            <div class="mt-2 flex flex-wrap gap-2">
                                                                @foreach ($design['jurisdictions'] ?? [] as $jurisdiction)
                                                                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ $jurisdictionLabels[$jurisdiction] ?? $jurisdiction }}</span>
                                                                @endforeach
                                                            </div>
                                                            <p class="mt-3 text-xs leading-5 text-text-secondary">Los principios de prevención y convivencia son generales. Cuando una regla depende del país, EDUDRIVE toma Costa Rica como referencia inicial.</p>
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-text">Referencias revisadas</p>
                                                            @forelse ($design['normative_sources'] ?? [] as $source)
                                                                @php
                                                                    $sourceHost = parse_url($source['url'], PHP_URL_HOST);
                                                                    $sourceReviewedAt = \Illuminate\Support\Carbon::parse($source['reviewed_at']);
                                                                    $sourceIsCurrent = $sourceReviewedAt->betweenIncluded(now()->subYear(), now());
                                                                @endphp
                                                                <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 block text-xs font-medium text-primary underline decoration-primary/40 underline-offset-2 hover:text-secondary">
                                                                    {{ $sourceLabels[$sourceHost] ?? $sourceHost ?? 'Fuente de referencia' }}
                                                                    <span class="block font-normal text-text-secondary">Revisión editorial: {{ \Illuminate\Support\Carbon::parse($source['reviewed_at'])->format('d/m/Y') }}</span>
                                                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold no-underline {{ $sourceIsCurrent ? 'bg-success/10 text-success-text' : 'bg-warning/10 text-warning-text' }}">{{ $sourceIsCurrent ? 'Revisión vigente' : 'Revisión pendiente' }}</span>
                                                                </a>
                                                            @empty
                                                                <p class="mt-2 text-xs text-text-secondary">Esta lección desarrolla principios generales de movilidad segura y no presenta una regla normativa específica.</p>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                    <p class="mt-4 border-t border-border pt-3 text-xs text-text-secondary">Contenido educativo, no asesoría legal ni preparación para obtener una licencia. Ante cambios normativos, prevalece la fuente oficial vigente.</p>
                                                </details>
                                            @endif
                                            @if ($design && collect($design['indicator_codes'] ?? [])->contains(fn (string $code): bool => str_starts_with($code, 'PEATON.')))
                                                <div class="mt-4">
                                                    @include(match ($lesson['code']) {
                                                        'RETO-ACTORES' => 'courses.blocks.road-actors-animation',
                                                        'RETO-RUTA-INCLUSIVA' => 'courses.blocks.inclusive-route-animation',
                                                        'RETO-CONDICIONES' => 'courses.blocks.changing-conditions-animation',
                                                        'RETO-DISTANCIA' => 'courses.blocks.speed-distance-animation',
                                                        default => $course['title'] === 'Misión Camino Seguro' ? 'courses.blocks.crossing-3d' : 'courses.blocks.safe-crossing-animation',
                                                    })
                                                </div>
                                            @endif
                                            @if ($design && collect($design['indicator_codes'] ?? [])->contains(fn (string $code): bool => str_starts_with($code, 'CICLISTA.')))
                                                <div class="mt-4">
                                                    @include(match ($lesson['code']) {
                                                        'RETO-CASCO' => 'courses.blocks.helmet-fit-animation',
                                                        'RETO-COMUNICA' => 'courses.blocks.cycling-signal-animation',
                                                        'RETO-SUPERFICIE', 'RETO-PUNTOS-CIEGOS' => 'courses.blocks.cycling-hazards-animation',
                                                        'BICI-RUTA-COMPARA', 'BICI-RUTA-PENDIENTE', 'BICI-INTERSECCION', 'BICI-PARADAS', 'BICI-RUTA-CAMBIA', 'BICI-RUTA-MISION' => 'courses.blocks.cycling-route-planner',
                                                        default => 'courses.blocks.visible-cycling-animation',
                                                    })
                                                </div>
                                            @endif
                                            @if ($design && collect($design['indicator_codes'] ?? [])->contains(fn (string $code): bool => str_starts_with($code, 'RIESGO.')))
                                                <div class="mt-4">
                                                    @include('courses.blocks.risk-radar-animation')
                                                </div>
                                            @endif
                                            @if ($design && collect($design['indicator_codes'] ?? [])->contains(fn (string $code): bool => str_starts_with($code, 'CONVIVENCIA.')))
                                                <div class="mt-4">
                                                    @include('courses.blocks.coexistence-signals-animation')
                                                </div>
                                            @endif
                                            @if ($design && collect($design['indicator_codes'] ?? [])->contains(fn (string $code): bool => str_starts_with($code, 'AUTOCUIDADO.')))
                                                <div class="mt-4">
                                                    @include('courses.blocks.self-care-pause-animation')
                                                </div>
                                            @endif
                                            @if ($design)
                                                <div class="mt-4">
                                                    @include('courses.blocks.experience-lab', ['design' => $design])
                                                </div>
                                            @endif
                                            </details>
                                            </div>
                                            @unless ($completed || $adminPreview)
                                                @php
                                                    $scenarioIds = collect($lesson['blocks'])->where('type', 'scenario')->pluck('id')->values();
                                                @endphp
                                                <form
                                                    method="POST"
                                                    action="{{ route('courses.lessons.complete', [$enrollment->id()->value(), $lesson['id']]) }}"
                                                    x-data="{
                                                        required: @js($scenarioIds),
                                                        correct: {},
                                                        startedAt: Date.now(),
                                                        reflectionText: @js(old('reflection', '')),
                                                        register(answer) {
                                                            if (this.required.includes(answer.id)) this.correct[answer.id] = answer.correct;
                                                        },
                                                        recordTime() {
                                                            const minutes = Math.max(1, Math.min(240, Math.ceil((Date.now() - this.startedAt) / 60000)));
                                                            this.$refs.timeSpent.value = minutes;
                                                        },
                                                        get solved() {
                                                            return this.required.every(id => this.correct[id] === true);
                                                        },
                                                        get solvedCount() {
                                                            return this.required.filter(id => this.correct[id] === true).length;
                                                        }
                                                    }"
                                                    @scenario-answered.window="register($event.detail)"
                                                    @submit="recordTime()"
                                                >
                                                    @csrf
                                                    <input x-ref="timeSpent" type="hidden" name="time_spent_minutes" value="1">
                                                    @if ($scenarioIds->count() > 1)
                                                        <div x-show="lessonPage === lastLessonPage" x-cloak class="mb-4 rounded-lg border border-safety bg-safety/10 p-4">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-warning-text">Evaluación formativa integradora</p>
                                                            <p class="mt-1 text-sm leading-6 text-text-secondary">Resolvé {{ $scenarioIds->count() }} situaciones. No hay nota ni límite de intentos: usá la retroalimentación para revisar cada decisión.</p>
                                                        </div>
                                                    @endif
                                            @endunless
                                            <div class="mt-4 flex flex-col gap-3">
                                                @foreach ($lesson['blocks'] as $block)
                                                    <section x-show="lessonPage === {{ $loop->iteration }}" x-cloak class="space-y-6 rounded-xl border border-border bg-surface p-6 sm:p-10" aria-label="{{ $block['payload']['title'] ?? 'Tema '.$loop->iteration }}">
                                                    <h4 class="text-2xl font-bold">{{ $block['payload']['title'] ?? 'Tema '.$loop->iteration }}</h4>
                                                    <div class="flex items-center gap-3" aria-hidden="true">
                                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">{{ $loop->iteration }}</span>
                                                        <span class="text-xs font-semibold uppercase tracking-wide text-text-secondary">
                                                            @if ($block['type'] === 'scenario') Decidí y comprobá @elseif ($loop->last) Aplicá lo aprendido @else Descubrí @endif
                                                        </span>
                                                        <span class="h-px flex-1 bg-border"></span>
                                                    </div>
                                                    @if ($block['type'] === 'text')
                                                        <div data-read-aloud class="lesson-page-copy max-w-prose text-lg leading-relaxed">{!! \Illuminate\Support\Str::markdown($block['payload']['markdown'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                                    @elseif ($block['type'] === 'scenario')
                                                        <div data-read-aloud>@include('courses.blocks.scenario', ['block' => $block, 'answerField' => ! $completed && ! $adminPreview, 'uniformEditorialFeedback' => true, 'courseLessonPage' => $loop->iteration])</div>
                                                    @endif
                                                    @include('courses.blocks.supplement', ['block' => $block])
                                                    </section>
                                                @endforeach
                                            </div>
                                            @unless ($completed || $adminPreview)
                                                <div x-show="lessonPage === lastLessonPage" x-cloak class="mt-4 rounded-xl border border-border bg-surface p-6 sm:p-10">
                                                    @if ($scenarioIds->isNotEmpty())
                                                        <p class="mb-3 text-sm text-text-secondary" aria-live="polite">
                                                            <span x-show="! solved">Decisiones seguras: <strong x-text="solvedCount"></strong> de {{ $scenarioIds->count() }}. Podés reintentar las que necesités.</span>
                                                            <span x-show="solved" x-cloak class="font-semibold text-success-text">¡Práctica superada! Ya podés registrar este aprendizaje.</span>
                                                        </p>
                                                    @endif
                                                    <div class="mb-4 rounded-md border border-primary/25 bg-primary/5 p-3 text-sm text-text-secondary">
                                                        <p class="font-bold text-text">Para completar esta lección</p>
                                                        <ol class="mt-2 grid gap-1">
                                                            <li><span class="font-bold text-primary">1.</span> Resolvé correctamente todas las decisiones de arriba.</li>
                                                            <li><span class="font-bold text-primary">2.</span> Elegí cómo te sentís para aplicar la conducta.</li>
                                                            <li><span class="font-bold text-primary">3.</span> Escribí una reflexión de al menos 10 caracteres.</li>
                                                        </ol>
                                                        @if (($design['requires_guardian'] ?? false) === true && $learnerStage['requires_guardian'] && $hasGuardian)
                                                            <p class="mt-2 font-medium text-primary">Al completar estos pasos, la práctica aparecerá en “Mi acompañamiento” de la persona adulta.</p>
                                                        @endif
                                                    </div>
                                                    <fieldset class="mb-4">
                                                        <legend class="text-sm font-bold text-text">¿Cómo te sentís para aplicar esta conducta?</legend>
                                                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                                            @foreach ([
                                                                'necesito_practicar' => 'Necesito practicar',
                                                                'voy_avanzando' => 'Voy avanzando',
                                                                'puedo_aplicarlo' => 'Puedo aplicarlo',
                                                            ] as $assessmentValue => $assessmentLabel)
                                                                <label class="flex cursor-pointer items-center gap-2 rounded-md border border-border bg-background px-3 py-2 text-sm text-text hover:border-primary">
                                                                    <input type="radio" name="self_assessment" value="{{ $assessmentValue }}" required @checked(old('self_assessment') === $assessmentValue) class="text-primary focus:ring-primary">
                                                                    <span>{{ $assessmentLabel }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </fieldset>
                                                    <label class="mb-4 block text-sm font-bold text-text" for="reflection-{{ $lesson['id'] }}">
                                                        {{ $learnerStage['reflection_prompt'] }}
                                                        @if (in_array($learnerStage['stage'], ['explore', 'discover', 'E1', 'E2'], true))
                                                            <span class="mt-2 block text-xs font-normal leading-5 text-text-secondary">Podés elegir una idea con ayuda de una persona adulta y cambiarla para contar lo que pensás.</span>
                                                            <span class="mt-2 flex flex-wrap gap-2" aria-label="Ideas para comenzar la reflexión">
                                                                @foreach (['Voy a detenerme y mirar con calma.', 'Voy a buscar un lugar más seguro.', 'Voy a pedir ayuda antes de acercarme a la vía.'] as $reflectionStarter)
                                                                    <button type="button" @click='reflectionText = @js($reflectionStarter)' class="min-h-11 rounded-md border border-primary/40 bg-background px-3 py-2 text-left text-xs font-medium text-primary hover:bg-primary/5 focus-visible:outline-none focus-visible:shadow-focus">{{ $reflectionStarter }}</button>
                                                                @endforeach
                                                            </span>
                                                        @endif
                                                        <textarea x-model="reflectionText" id="reflection-{{ $lesson['id'] }}" name="reflection" rows="3" minlength="10" maxlength="500" required class="mt-2 block w-full rounded-md border-border bg-background text-sm text-text focus:border-primary focus:ring-primary" placeholder="Escribí una idea breve con tus propias palabras."></textarea>
                                                        <span class="mt-1 block text-xs font-normal text-text-secondary">No tiene nota. Nos ayuda a comprender cómo estás llevando el aprendizaje a la vida real.</span>
                                                    </label>
                                                    <p class="mb-3 text-xs text-text-secondary">⏱ Al completar, guardaremos únicamente los minutos dedicados a esta lección para ayudarte a comprender tu recorrido.</p>
                                                    <x-ui.button
                                                        type="submit"
                                                        size="sm"
                                                        x-bind:disabled="! solved"
                                                        x-bind:aria-disabled="(! solved).toString()"
                                                        x-bind:class="! solved ? 'cursor-not-allowed opacity-50' : ''"
                                                    >{{ (($design['requires_guardian'] ?? false) === true && $learnerStage['requires_guardian'] && $hasGuardian) ? 'Completar lección y avisar al acompañante' : 'Verificar y completar lección' }}</x-ui.button>
                                                </div>
                                                </form>
                                            @endunless
                                            @if ($completed || $adminPreview)
                                                <div x-show="lessonPage === lastLessonPage" x-cloak class="mt-5 rounded-xl border border-border p-6 text-lg leading-relaxed">
                                                    <h4 class="text-2xl font-bold">Lo que te llevás</h4>
                                                    <p class="mt-4">{{ $design['behavior_objective'] ?? $lesson['summary'] }}</p>
                                                    <p class="mt-4">{{ $completed ? 'Esta lección ya está registrada. Repasarla no cambia tu avance.' : 'Vista de administración: las respuestas de esta prueba no registran progreso.' }}</p>
                                                </div>
                                            @endif
                                            @include('courses.blocks.lesson-page-navigation', ['navigationPosition' => 'bottom'])
                                            </div>
                                        @endif
                                    </details>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($currentModuleStat['percentage'] === 100)
                    @php
                        $nextModule = $course['modules'][$loop->index + 1] ?? null;
                        $moduleAchievements = match ($module['code']) {
                            'MISION-RIESGO' => ['Detectaste peligros visibles y ocultos', 'Anticipaste movimientos posibles', 'Elegiste decisiones con margen', 'Replanteaste el plan ante cambios'],
                            'MISION-CONVIVENCIA' => ['Interpretaste señales por su función', 'Comprobaste la situación aunque tuvieras prioridad', 'Comunicaste movimientos previsibles', 'Protegiste espacios compartidos y accesibles'],
                            'MISION-AUTOCUIDADO' => ['Retiraste distractores antes de actuar', 'Reconociste señales de cansancio, prisa o enojo', 'Aplicaste una pausa para recuperar atención', 'Mantuviste decisiones seguras ante la presión'],
                            'MISION-BICI' => ['Revisaste bicicleta y casco antes de salir', 'Aumentaste tu visibilidad', 'Comunicaste movimientos con anticipación', 'Conservaste atención y margen'],
                            'MISION-RUTA-BICI' => ['Comparaste rutas por seguridad', 'Preparaste las intersecciones antes de entrar', 'Evitaste puertas y puntos ciegos', 'Cambiaste el plan ante condiciones nuevas'],
                            'MISION-BICI' => ['Revisaste bicicleta, casco y carga', 'Aumentaste tu visibilidad', 'Comunicaste movimientos previsibles', 'Anticipaste superficies y puntos ciegos'],
                            default => ['Elegiste lugares de cruce más seguros', 'Observaste y escuchaste antes de actuar', 'Consideraste a todas las personas', 'Completaste la secuencia con calma'],
                        };
                    @endphp
                    <footer class="border-t border-success/30 bg-success/5 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-success text-2xl text-white" aria-hidden="true">✓</div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-success-text">Misión completada</p>
                                    <h3 class="mt-1 font-heading text-lg font-bold text-text">Este aprendizaje ya forma parte de tu recorrido vial</h3>
                                    <ul class="mt-3 grid gap-x-5 gap-y-1 text-sm text-text-secondary sm:grid-cols-2">
                                        @foreach ($moduleAchievements as $achievement)
                                            <li class="flex gap-2"><span class="text-success" aria-hidden="true">✓</span><span>{{ $achievement }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2 lg:flex-col">
                                <a href="{{ route('road-passport.show') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-success px-4 py-2 text-sm font-bold text-success-text hover:bg-success/10 focus-visible:outline-none focus-visible:shadow-focus">Ver evidencia</a>
                                @if ($nextModule)
                                    <a href="#module-{{ $nextModule['id'] }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus">Ir a la siguiente misión →</a>
                                @endif
                            </div>
                        </div>
                    </footer>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.app>
