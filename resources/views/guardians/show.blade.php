<x-layouts.app :title="'EDUDRIVE — Progreso de '.$profile['name']">
    <div class="flex flex-col gap-6">
        @if (session('success'))
            <div class="rounded-lg border border-success/30 bg-success/10 p-4 text-sm font-medium text-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm text-danger" role="alert">
                <p class="font-bold">Revisá la información de la práctica:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        <a href="{{ route('guardians.web.index') }}" class="inline-flex min-h-11 items-center self-start rounded-md px-2 text-sm font-semibold text-primary hover:bg-primary/10">← Volver a mi acompañamiento</a>
        <section class="relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-7 text-white shadow-md sm:px-8" aria-labelledby="minor-progress-title">
            <div class="absolute -right-16 -top-20 h-52 w-52 rounded-full bg-[#008a78]/55" aria-hidden="true"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-5">
                <div><p class="text-sm font-bold uppercase tracking-[0.16em] text-[#5bd6c0]">Acompañamiento activo</p><h1 id="minor-progress-title" class="mt-2 font-heading text-3xl font-bold">Progreso de {{ $profile['name'] }}</h1><p class="mt-2 text-sm text-white/75">Información compartida de forma segura por la relación de acompañamiento.</p></div>
                <div class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white/30 bg-white/10 text-3xl" aria-hidden="true">🤝</div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.card>
                <p class="text-sm text-text-secondary">Pasaporte Vial</p>
                @if ($profile['road_passport'])
                    <p class="mt-1 font-heading text-2xl font-bold text-primary">Nivel {{ $profile['road_passport']['level'] }}</p>
                    <p class="text-sm text-text-secondary">Estado: {{ ['active' => 'Activo', 'suspended' => 'Suspendido', 'revoked' => 'Revocado'][$profile['road_passport']['status']] ?? $profile['road_passport']['status'] }}</p>
                @else
                    <p class="mt-2 text-sm text-text-secondary">Todavía no tiene un pasaporte emitido.</p>
                @endif
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-text-secondary">Cómo acompañar</p>
                <p class="mt-1 text-sm leading-6">Preguntá qué decisión tomó, qué peligro identificó y cómo aplicaría lo aprendido en un recorrido cotidiano.</p>
            </x-ui.card>
        </div>

        <section class="rounded-xl border-2 border-primary/30 bg-surface p-5" aria-labelledby="practice-inbox-title">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Bandeja de prácticas</p><h2 id="practice-inbox-title" class="mt-1 font-heading text-xl font-bold text-text">Qué necesita acompañamiento</h2></div>
                @if ($profile['pending_observations_count'] > 0)<span class="rounded-full bg-warning/10 px-3 py-1 text-xs font-bold text-warning-text">{{ $profile['pending_observations_count'] }} pendiente{{ $profile['pending_observations_count'] === 1 ? '' : 's' }}</span>@else<span class="rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success-text">Al día</span>@endif
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg bg-background p-4"><p class="font-heading text-3xl font-bold text-warning-text">{{ $profile['pending_observations_count'] }}</p><p class="mt-1 text-sm text-text-secondary">Lecciones listas para práctica observada</p></div>
                <div class="rounded-lg bg-background p-4"><p class="font-heading text-3xl font-bold text-success-text">{{ $profile['recorded_observations_count'] }}</p><p class="mt-1 text-sm text-text-secondary">Prácticas registradas por vos</p></div>
            </div>
            @if ($profile['next_pending_practice'])
                <div class="mt-4 rounded-lg border border-primary/25 bg-primary/5 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Próxima sugerida</p>
                    <p class="mt-1 font-heading font-bold text-text">{{ $profile['next_pending_practice']['lesson_title'] }}</p>
                    <p class="mt-1 text-sm text-text-secondary">{{ $profile['next_pending_practice']['course_title'] }}</p>
                    <a href="#practice-{{ $profile['selected_lesson_id'] }}" class="mt-3 inline-flex min-h-11 items-center rounded-md bg-primary px-4 text-sm font-bold text-white hover:bg-secondary">Ir al formulario de observación ↓</a>
                </div>
            @else
                <p class="mt-4 text-sm leading-6 text-text-secondary">No hay prácticas nuevas por registrar. Cuando el estudiante complete otra lección con acompañamiento, aparecerá aquí.</p>
            @endif
        </section>

        <section aria-labelledby="minor-courses-title">
            <h2 id="minor-courses-title" class="mb-3 font-heading text-xl font-bold">Misiones y cursos</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($profile['enrollments'] as $enrollment)
                    @php
                        $percentage = $enrollment['progress']['progress_percentage'];
                        $pendingObservationLessons = collect($enrollment['completed_lesson_options'])->whereNull('observation');
                        $recordedObservations = collect($enrollment['completed_lesson_options'])->whereNotNull('observation');
                        $containsSelectedLesson = $pendingObservationLessons->contains('id', $profile['selected_lesson_id']);
                        $behaviorLabels = ['identified_risk' => 'Identificó un riesgo antes de actuar', 'made_safe_decision' => 'Tomó y explicó una decisión segura', 'applied_safe_sequence' => 'Aplicó la secuencia segura aprendida'];
                        $contextLabels = ['controlled_space' => 'Espacio controlado sin tránsito', 'tabletop_model' => 'Maqueta o representación', 'school_route' => 'Ruta escolar desde un punto protegido', 'neighborhood' => 'Barrio desde un punto protegido'];
                    @endphp
                    <article class="rounded-xl border border-border bg-surface p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-heading font-bold">{{ $enrollment['course_title'] }}</h3>
                            <span class="text-sm font-bold text-primary">{{ $percentage }}%</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $percentage }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-text-secondary">{{ $enrollment['progress']['completed_lessons_count'] }} de {{ $enrollment['progress']['total_lessons'] }} lecciones completadas</p>
                        @if ($recordedObservations->isNotEmpty())
                            <div class="mt-4 border-t border-border pt-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-text-secondary">Prácticas que acompañaste</p>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($recordedObservations as $lesson)
                                        <li class="rounded-lg bg-background p-3 text-xs">
                                            <span class="block font-bold text-text">✓ {{ $lesson['title'] }}</span>
                                            <span class="mt-1 block text-text-secondary">{{ $behaviorLabels[$lesson['observation']['behavior']] ?? 'Conducta segura observada' }} · {{ $lesson['observation']['occurred_at'] }}</span>
                                            @if (! empty($lesson['observation']['practice_context']))<span class="mt-1 block text-text-secondary"><strong>Contexto:</strong> {{ $contextLabels[$lesson['observation']['practice_context']] ?? 'Entorno seguro' }}</span>@endif
                                            @if ($lesson['observation']['note'])
                                                <span class="mt-1 block italic text-text-secondary">“{{ $lesson['observation']['note'] }}”</span>
                                            @endif
                                            @if ($lesson['observation']['reflection'])
                                                <div class="mt-3 rounded-lg border border-primary/20 bg-primary/5 p-3">
                                                    <span class="block font-bold text-primary">Respuesta del estudiante · {{ $lesson['observation']['reflection']['occurred_at'] }}</span>
                                                    <span class="mt-1 block text-text-secondary"><strong>Aprendí:</strong> {{ $lesson['observation']['reflection']['learned'] }}</span>
                                                    <span class="mt-1 block text-text-secondary"><strong>La próxima vez:</strong> {{ $lesson['observation']['reflection']['next_action'] }}</span>
                                                </div>
                                            @else
                                                <span class="mt-2 block text-text-secondary">Esperando la reflexión del estudiante.</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($profile['road_passport'] && $pendingObservationLessons->isNotEmpty())
                            <details id="{{ $containsSelectedLesson ? 'practice-'.$profile['selected_lesson_id'] : 'practice-form-'.$enrollment['enrollment_id'] }}" class="scroll-mt-6 mt-4 rounded-lg border border-primary/30 bg-primary/5 p-4" @if ($containsSelectedLesson) open @endif>
                                <summary class="cursor-pointer text-sm font-bold text-primary">Registrar una práctica acompañada</summary>
                                <p class="mt-3 rounded-md border border-safety/40 bg-safety/10 p-3 text-xs leading-5 text-warning-text">Registrá únicamente una experiencia real que ocurrió en un espacio seguro. No realicen la práctica mientras completan este formulario.</p>
                                <form method="POST" action="{{ route('guardians.web.observations.store', $profile['user_id']) }}" class="mt-4 grid gap-3">
                                    @csrf
                                    <input type="hidden" name="enrollment_id" value="{{ $enrollment['enrollment_id'] }}">
                                    <label class="grid gap-1 text-sm font-medium">Lección practicada
                                        <select name="lesson_id" required class="rounded-lg border border-border bg-background px-3 py-2">
                                            @foreach ($pendingObservationLessons as $lesson)
                                                <option value="{{ $lesson['id'] }}" @selected($lesson['id'] === $profile['selected_lesson_id'])>{{ $lesson['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="grid gap-1 text-sm font-medium">¿Qué conducta observaste?
                                        <select name="behavior" required class="rounded-lg border border-border bg-background px-3 py-2">
                                            <option value="identified_risk">Identificó un riesgo antes de actuar</option>
                                            <option value="made_safe_decision">Tomó y explicó una decisión segura</option>
                                            <option value="applied_safe_sequence">Aplicó la secuencia segura aprendida</option>
                                        </select>
                                    </label>
                                    <label class="grid gap-1 text-sm font-medium">¿Dónde realizaron la práctica?
                                        <select name="practice_context" required class="rounded-lg border border-border bg-background px-3 py-2">
                                            <option value="controlled_space">Espacio controlado sin tránsito</option>
                                            <option value="tabletop_model">Maqueta o representación</option>
                                            <option value="school_route">Ruta escolar desde un punto protegido</option>
                                            <option value="neighborhood">Barrio desde un punto protegido</option>
                                        </select>
                                    </label>
                                    <label class="grid gap-1 text-sm font-medium">¿Qué observaste?
                                        <textarea name="note" required minlength="10" maxlength="500" rows="3" class="rounded-lg border border-border bg-background px-3 py-2" placeholder="Describí la situación, la decisión y lo que conversaron."></textarea>
                                    </label>
                                    <label class="flex items-start gap-2 text-xs text-text-secondary">
                                        <input type="checkbox" name="safe_environment" value="1" required class="mt-0.5">
                                        Confirmo que la práctica ocurrió en un entorno seguro y bajo supervisión.
                                    </label>
                                    <button class="min-h-11 w-fit rounded-lg bg-primary px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-secondary">Guardar observación segura</button>
                                </form>
                            </details>
                        @elseif (! $profile['road_passport'] && count($enrollment['completed_lesson_options']) > 0)
                            <p class="mt-4 border-t border-border pt-4 text-xs text-text-secondary">Cuando tenga su Pasaporte Vial activo, podrás registrar aquí prácticas acompañadas.</p>
                        @elseif ($profile['road_passport'] && $recordedObservations->isNotEmpty())
                            <p class="mt-3 text-xs font-medium text-success">Ya acompañaste todas las lecciones completadas hasta ahora.</p>
                        @endif
                    </article>
                @empty
                    <x-ui.card><p class="text-sm text-text-secondary">Todavía no está inscrito en cursos.</p></x-ui.card>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
