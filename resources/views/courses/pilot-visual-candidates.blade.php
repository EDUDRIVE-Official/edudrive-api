<x-layouts.app title="EDUDRIVE — Candidaturas visuales">
    @php
        $names = ['van' => 'La van que bloquea la vista', 'turn' => 'El carro que gira', 'barrier' => 'Ruta interrumpida', 'descent' => 'Cambió el lugar de descenso'];
        $sceneRoutes = ['van' => 'pilot-instruments.van', 'turn' => 'pilot-instruments.visual', 'barrier' => 'pilot-instruments.barrier', 'descent' => 'pilot-instruments.descent'];
    @endphp
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm">Conexión editorial interna · Versión {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Escenas candidatas para lecciones</h1>
            <p>Una candidatura identifica dónde podría integrarse una escena ya revisada. No inserta bloques, no cambia el curso y no publica contenido.</p>
            <div class="flex flex-wrap gap-3"><a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('pilot-instruments.visual-readiness') }}">Ver preparación del piloto</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-review-summary') }}">Volver a cobertura</a></div>
        </header>

        @if(session('status'))<p class="rounded border border-green-500 bg-green-50 p-4 text-green-950" role="status">{{ session('status') }}</p>@endif

        @if($draftLessons === [])
            <aside class="space-y-3 rounded border border-amber-400 bg-amber-50 p-4 text-amber-950">
                <p><strong>No hay lecciones elegibles.</strong> Primero debe existir una lección dentro de un curso en estado borrador. Los cursos publicados nunca aparecen en esta lista.</p>
                <form method="POST" action="{{ route('pilot-instruments.visual-candidates.create-draft') }}">@csrf<button class="min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" type="submit">Crear espacio borrador P912</button></form>
            </aside>
        @else
            <aside class="rounded border border-green-400 bg-green-50 p-4 text-green-950">
                <p class="font-semibold">Espacios borrador disponibles</p>
                <ul class="mt-2 list-disc pl-5">@foreach(collect($draftLessons)->unique('course_id') as $lesson)<li>{{ $lesson['course_title'] }} · <a class="font-semibold text-primary underline" href="{{ route('courses.show', $lesson['course_id']) }}">Abrir borrador</a></li>@endforeach</ul>
                <p class="mt-2 text-sm">Sus lecciones aparecerán para selección únicamente cuando una escena complete las tres revisiones.</p>
            </aside>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            @foreach($coverage as $scene => $state)
                @php($binding = $bindings[$scene] ?? null)
                <section class="space-y-4 rounded-xl border border-border bg-surface p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-xl font-bold">{{ $names[$scene] }}</h2>
                        <span class="rounded-full px-3 py-1 text-sm {{ $state['coverage_complete'] ? 'bg-green-100 text-green-900' : 'bg-amber-100 text-amber-900' }}">{{ $state['coverage_complete'] ? 'Puede postularse' : 'Revisión incompleta' }}</span>
                    </div>
                    <a class="font-semibold text-primary underline" href="{{ route($sceneRoutes[$scene]) }}">Abrir escena</a>

                    @if($binding)
                        <div class="rounded border border-blue-300 bg-blue-50 p-3 text-blue-950">
                            <p class="font-semibold">Candidatura actual</p>
                            <p>{{ $binding['course_title'] }} · {{ $binding['module_title'] }} · {{ $binding['unit_title'] }} · {{ $binding['lesson_title'] }}</p>
                            <p class="mt-1 text-sm">Estado actual del curso: {{ $binding['course_status'] }}. La candidatura no alteró la lección.</p>
                        </div>
                    @endif

                    @if($state['coverage_complete'] && $draftLessons !== [])
                        <form class="space-y-3" method="POST" action="{{ route('pilot-instruments.visual-candidates.store') }}">
                            @csrf
                            <input type="hidden" name="scene" value="{{ $scene }}">
                            <label class="block space-y-1"><span class="font-semibold">Lección en borrador</span>
                                <select class="w-full rounded border border-border bg-background p-3" name="lesson_id" required>
                                    <option value="">Seleccioná una lección</option>
                                    @foreach($draftLessons as $lesson)<option value="{{ $lesson['lesson_id'] }}">{{ $lesson['course_title'] }} · {{ $lesson['module_title'] }} · {{ $lesson['unit_title'] }} · {{ $lesson['lesson_title'] }}</option>@endforeach
                                </select>
                            </label>
                            <button class="min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" type="submit">Guardar como candidata</button>
                        </form>
                    @else
                        <p class="text-sm text-text-secondary">Debe completar Docencia, Seguridad vial y Accesibilidad sin cambios pendientes antes de elegir una lección.</p>
                    @endif
                </section>
            @endforeach
        </div>

        <aside class="rounded border border-amber-400 bg-amber-50 p-4 text-amber-950"><strong>Límite:</strong> esta pantalla solo registra intención editorial. Integrar la escena requerirá una acción posterior, controlada y versionada sobre el curso en borrador.</aside>
    </div>
</x-layouts.app>
