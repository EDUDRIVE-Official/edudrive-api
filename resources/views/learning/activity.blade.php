<x-layouts.app title="EDUDRIVE — Actividad de aprendizaje">
    <div class="mx-auto max-w-3xl space-y-6">
        @php
            $indicatorLabels = [
                'RIESGO.DETECTA' => 'Detecté peligros visibles y ocultos',
                'RIESGO.ANTICIPA' => 'Anticipé lo que podría ocurrir',
                'RIESGO.MARGEN' => 'Elegí tiempo y espacio para reaccionar',
                'RIESGO.REPLANIFICA' => 'Cambié el plan ante información nueva',
                'PEATON.LUGAR' => 'Elegí un lugar seguro',
                'PEATON.PAUSA' => 'Me detuve antes de actuar',
                'PEATON.OBSERVA' => 'Observé y escuché el entorno',
                'PEATON.CRUZA' => 'Completé el cruce con atención',
                'CONVIVENCIA.SENAL' => 'Interpreté la función de las señales',
                'CONVIVENCIA.PRIORIDAD' => 'Comprobé la seguridad además de la prioridad',
                'CONVIVENCIA.COMUNICA' => 'Actué de forma visible y predecible',
                'CONVIVENCIA.RESUELVE' => 'Resolví señales o intereses contradictorios',
                'AUTOCUIDADO.DISTRACTOR' => 'Retiré distractores antes de actuar',
                'AUTOCUIDADO.ESTADO' => 'Reconocí cómo mi estado afectaba la decisión',
                'AUTOCUIDADO.PAUSA' => 'Hice una pausa para recuperar atención',
                'AUTOCUIDADO.PRESION' => 'Mantuve una decisión segura ante la presión',
                'CICLISTA.REVISA' => 'Revisé bicicleta, casco y equipo',
                'CICLISTA.VISIBLE' => 'Aumenté mi visibilidad',
                'CICLISTA.ATIENDE' => 'Mantuve atención y control',
                'CICLISTA.ANTICIPA' => 'Anticipé un peligro antes de acercarme',
                'CICLISTA.RUTA.ELIGE' => 'Elegí una ruta con mayor margen',
                'CICLISTA.RUTA.INTERSECCION' => 'Preparé la intersección antes de entrar',
                'CICLISTA.RUTA.POSICION' => 'Evité puntos ciegos y conservé espacio',
                'CICLISTA.RUTA.REPLANIFICA' => 'Cambié la ruta ante condiciones nuevas',
                'CICLISTA.REVISA' => 'Comprobé bicicleta, casco y carga antes de salir',
                'CICLISTA.VISIBLE' => 'Elegí cómo hacerme visible a tiempo',
                'CICLISTA.ATIENDE' => 'Mantuve ojos, oídos y manos disponibles',
                'CICLISTA.ANTICIPA' => 'Anticipé peligros y conservé una salida segura',
            ];
            $selfAssessmentLabels = [
                'necesito_practicar' => 'Necesito practicar',
                'voy_avanzando' => 'Voy avanzando',
                'puedo_aplicarlo' => 'Puedo aplicarlo',
            ];
        @endphp
        <div><a href="{{ route('student-profile.show') }}" class="text-sm text-primary">← Volver a mi perfil</a><h1 class="mt-2 font-heading text-2xl font-bold">Mi bitácora de aprendizaje</h1><p class="mt-1 text-sm text-text-secondary">Aquí podés revisar qué aprendiste y cómo avanzaste. Tu identificador de matrícula permanece oculto por privacidad.</p></div>
        <div class="grid gap-4 sm:grid-cols-3"><x-ui.card><p class="text-sm text-text-secondary">Eventos</p><p class="mt-1 font-heading text-3xl font-bold">{{ count($activity['events']) }}</p></x-ui.card><x-ui.card><p class="text-sm text-text-secondary">Lecciones completadas</p><p class="mt-1 font-heading text-3xl font-bold">{{ collect($activity['events'])->where('verb', 'lesson_completed')->count() }}</p></x-ui.card><x-ui.card><p class="text-sm text-text-secondary">Exámenes enviados</p><p class="mt-1 font-heading text-3xl font-bold">{{ collect($activity['events'])->where('verb', 'exam_attempt_submitted')->count() }}</p></x-ui.card></div>
        <div class="space-y-3">
            @forelse (array_reverse($activity['events']) as $event)
                @php
                    $evidence = $event['evidence'];
                    $isLesson = $event['verb'] === 'lesson_completed';
                    $scenarioCount = count($evidence['scenario_results'] ?? []);
                @endphp
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-success">✓ {{ $isLesson ? 'Lección completada' : 'Evaluación enviada' }}</p>
                            <h2 class="mt-1 font-heading text-lg font-bold">{{ $evidence['lesson_title'] ?? ($isLesson ? 'Aprendizaje registrado' : 'Evaluación registrada') }}</h2>
                            @if (! empty($evidence['lesson_code']))<p class="mt-1 text-xs text-text-secondary">{{ $evidence['lesson_code'] }}</p>@endif
                            @if (! empty($evidence['learning_design_version']))<p class="mt-1 text-xs text-text-secondary">Versión pedagógica {{ $evidence['learning_design_version'] }} · {{ collect($evidence['jurisdictions'] ?? [])->map(fn ($context) => ['CR' => 'Costa Rica', 'GLOBAL' => 'Aplicación universal'][$context] ?? $context)->implode(' + ') }}</p>@endif
                        </div>
                        <time class="text-sm text-text-secondary" datetime="{{ $event['occurred_at'] }}">{{ \Illuminate\Support\Carbon::parse($event['occurred_at'])->format('d/m/Y · H:i') }}</time>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @if (array_key_exists('time_spent_minutes', $evidence))
                            <div class="rounded-lg bg-background p-3"><p class="text-xs text-text-secondary">Tiempo dedicado</p><p class="mt-1 font-bold">{{ $evidence['time_spent_minutes'] }} minuto(s)</p></div>
                        @endif
                        @if ($scenarioCount > 0)
                            <div class="rounded-lg bg-background p-3"><p class="text-xs text-text-secondary">Decisiones practicadas</p><p class="mt-1 font-bold">{{ $scenarioCount }} resuelta(s) correctamente</p></div>
                        @endif
                        @if (array_key_exists('score', $evidence))
                            <div class="rounded-lg bg-background p-3"><p class="text-xs text-text-secondary">Resultado</p><p class="mt-1 font-bold">{{ $evidence['score'] }}</p></div>
                        @endif
                        @if (array_key_exists('passed', $evidence))
                            <div class="rounded-lg bg-background p-3"><p class="text-xs text-text-secondary">Estado</p><p class="mt-1 font-bold">{{ $evidence['passed'] ? 'Logrado' : 'Seguí practicando' }}</p></div>
                        @endif
                    </div>

                    @if (! empty($evidence['indicator_codes']))
                        <div class="mt-4 border-t border-border pt-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-primary">{{ $isLesson ? 'Habilidades practicadas' : 'Indicadores de la evaluación' }}</p>
                            <ul class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                                @foreach ($evidence['indicator_codes'] as $indicator)
                                    <li class="flex gap-2"><span class="text-success" aria-hidden="true">✓</span><span>{{ $indicatorLabels[$indicator] ?? 'Apliqué una habilidad de seguridad vial' }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($evidence['reflection']) || ! empty($evidence['self_assessment']))
                        <div class="mt-4 rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-primary">Mi cierre de la lección</p>
                            @if (! empty($evidence['self_assessment']))
                                <p class="mt-2 text-sm text-text-secondary"><strong class="text-text">Así me sentí:</strong> {{ $selfAssessmentLabels[$evidence['self_assessment']] ?? 'Autoevaluación registrada' }}</p>
                            @endif
                            @if (! empty($evidence['reflection']))
                                <p class="mt-2 text-sm leading-6 text-text-secondary"><strong class="text-text">Lo expresé con mis palabras:</strong> {{ $evidence['reflection'] }}</p>
                            @endif
                        </div>
                    @endif
                </x-ui.card>
            @empty
                <x-ui.card><p class="text-sm text-text-secondary">Todavía no hay actividad registrada para esta matrícula.</p></x-ui.card>
            @endforelse
        </div>
    </div>
</x-layouts.app>
