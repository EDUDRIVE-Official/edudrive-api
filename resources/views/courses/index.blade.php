<x-layouts.app title="EDUDRIVE — Cursos">
    @php
        $courseCollection = collect($courses);
        $enrolledCount = $courseCollection->filter(fn (array $course): bool => ! empty($course['enrollment']))->count();
        $completedCount = $courseCollection->filter(fn (array $course): bool => (int) ($course['progress']['progress_percentage'] ?? 0) === 100)->count();
        $recommendedCount = $courseCollection->where('is_recommended', true)->count();
    @endphp
    <div class="course-catalog flex flex-col gap-7" x-data="{ query: '', filter: 'all' }">
        <section class="catalog-hero relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="courses-title">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[#008a78]/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-[#f5b700]/20" aria-hidden="true"></div>
            <div class="relative flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div class="max-w-2xl">
                    <p class="hero-eyebrow text-sm font-bold uppercase tracking-[0.16em] text-[#5bd6c0]">TU PRÓXIMA DECISIÓN EMPIEZA AQUÍ</p>
                    <h1 id="courses-title" class="mt-2 font-heading text-3xl font-bold sm:text-4xl">Tu recorrido de educación vial</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-white/85">Consultá el público de cada curso y avanzá a tu ritmo. Tu práctica queda registrada en el Pasaporte Vial.</p>
                </div>
                <img src="{{ asset('brand/mobility-campus.svg') }}" alt="" class="catalog-hero-art" aria-hidden="true">
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading text-2xl font-bold">Explorá los cursos</h2>
                <p class="mt-1 text-sm text-text-secondary">Encontrá tu siguiente oportunidad de aprendizaje.</p>
            </div>
            @if ($canManage)
                @if (app()->environment(['local', 'testing']) || config('pilot.instruments_enabled', false))
                    <a href="{{ route('pilot-instruments.editorial-preview') }}" class="rounded-lg border border-primary p-3 font-semibold underline">Probar borrador: Camino Seguro y Pasajero Responsable</a>
                @endif
                <a
                    href="{{ route('courses.create') }}"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-sm bg-primary px-4 font-sans font-medium text-white transition hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Nuevo curso
                </a>
            @endif
        </div>

        <section class="catalog-route rounded-xl border border-primary/30 bg-primary/5 px-5 py-5" aria-labelledby="learning-route-title">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Tu ruta EDUDRIVE</p>
                    <h2 id="learning-route-title" class="font-heading text-lg font-bold">{{ $learnerStage['identity'] }}</h2>
                </div>
                <span class="rounded-full bg-surface px-3 py-1 text-xs font-medium text-text-secondary">{{ $learnerStage['age_range'] }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-text-secondary">{{ $learnerStage['guidance'] }}</p>
            @if ($recommendedCount === 0)
                <p class="mt-3 text-sm">Todavía no hay una recomendación por etapa para tu perfil. Podés consultar el catálogo y continuar tus cursos; para elegir uno nuevo, solicitá orientación docente.</p>
            @endif
            @if ($canManage)
                <p class="mt-3 text-sm">Clasificación del catálogo: {{ $courseCollection->filter(fn (array $course): bool => $course['audience']['stage'] !== null)->count() }} de {{ $courseCollection->count() }} cursos tienen una etapa única en todas sus lecciones. Esta clasificación no sustituye la revisión del contenido.</p>
            @endif
        </section>

        <section class="catalog-stats grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Resumen de cursos">
            <div class="rounded-lg border border-border bg-surface p-4 shadow-xs"><p class="text-xs font-semibold text-text-secondary">Disponibles</p><p class="mt-1 font-heading text-2xl font-bold text-primary">{{ $courseCollection->count() }}</p></div>
            <div class="rounded-lg border border-border bg-surface p-4 shadow-xs"><p class="text-xs font-semibold text-text-secondary">En tu recorrido</p><p class="mt-1 font-heading text-2xl font-bold text-secondary">{{ $enrolledCount }}</p></div>
            <div class="rounded-lg border border-border bg-surface p-4 shadow-xs"><p class="text-xs font-semibold text-text-secondary">Completados</p><p class="mt-1 font-heading text-2xl font-bold text-success-text">{{ $completedCount }}</p></div>
            <div class="rounded-lg border border-border bg-surface p-4 shadow-xs"><p class="text-xs font-semibold text-text-secondary">Recomendados</p><p class="mt-1 font-heading text-2xl font-bold text-warning-text">{{ $recommendedCount }}</p></div>
        </section>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        @if (session('error'))
            <p class="font-sans text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        <section class="rounded-xl border border-border bg-surface p-4 shadow-sm" aria-label="Buscar y filtrar cursos">
            <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                <div>
                    <label for="course-search" class="mb-1 block text-sm font-semibold text-text">Buscar por nombre o código</label>
                    <input id="course-search" type="search" x-model="query" placeholder="Ejemplo: bicicleta, peatón o convivencia" class="min-h-11 w-full rounded-md border border-border bg-background px-4 text-base text-text placeholder:text-text-secondary focus-visible:outline-none focus-visible:shadow-focus">
                </div>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar cursos">
                    @foreach ([['all', 'Todos'], ['recommended', 'Para vos'], ['enrolled', 'En curso'], ['completed', 'Completados']] as [$value, $label])
                        <button type="button" @click="filter = '{{ $value }}'" :aria-pressed="filter === '{{ $value }}'" :class="filter === '{{ $value }}' ? 'bg-primary text-white' : 'border border-border bg-surface text-text hover:bg-background'" class="min-h-11 rounded-md px-4 text-sm font-semibold transition">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="catalog-grid grid gap-5 lg:grid-cols-2">
            @forelse ($courses as $course)
                @php
                    $status = \Modules\Academic\Domain\Enums\CourseStatus::from($course['status']);
                    $statusVariant = match ($status) {
                        \Modules\Academic\Domain\Enums\CourseStatus::Draft => 'warning',
                        \Modules\Academic\Domain\Enums\CourseStatus::UnderReview, \Modules\Academic\Domain\Enums\CourseStatus::Approved => 'info',
                        \Modules\Academic\Domain\Enums\CourseStatus::Published => 'success',
                        \Modules\Academic\Domain\Enums\CourseStatus::Archived => 'danger',
                    };
                    $percentage = $course['progress']['progress_percentage'] ?? 0;
                    $titleForIcon = mb_strtolower($course['title'].' '.$course['code']);
                    $courseIcon = match (true) {
                        str_contains($titleForIcon, 'cicli'), str_contains($titleForIcon, 'bici') => '🚲',
                        str_contains($titleForIcon, 'peat'), str_contains($titleForIcon, 'cruce') => '🚶',
                        str_contains($titleForIcon, 'moto') => '🏍️',
                        str_contains($titleForIcon, 'niñ'), str_contains($titleForIcon, 'escuel') => '🎒',
                        str_contains($titleForIcon, 'emerg'), str_contains($titleForIcon, 'incident') => '🆘',
                        str_contains($titleForIcon, 'ambiente'), str_contains($titleForIcon, 'sosten') => '🌱',
                        str_contains($titleForIcon, 'pasaj') => '🚌',
                        default => '🛣️',
                    };
                    $searchableCourse = mb_strtolower($course['title'].' '.$course['code'].' '.($course['description'] ?? ''));
                    $filterState = $percentage === 100 ? 'completed' : (! empty($course['enrollment']) ? 'enrolled' : 'available');
                @endphp
                <article
                    x-show="(@js($searchableCourse).includes(query.toLocaleLowerCase())) && (filter === 'all' || (filter === 'recommended' && @js((bool) $course['is_recommended'])) || filter === @js($filterState))"
                    x-transition.opacity
                    class="catalog-card group flex flex-col overflow-hidden rounded-xl border {{ $course['is_recommended'] ? 'border-primary ring-2 ring-primary/15' : 'border-border' }} bg-surface shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="catalog-cover relative flex min-h-28 items-center justify-between overflow-hidden bg-gradient-to-br from-primary/15 via-background to-secondary/15 px-5 py-4">
                        <img src="{{ asset('brand/mobility-campus.svg') }}" alt="" aria-hidden="true" class="catalog-cover-art">
                        <div class="absolute -right-8 -top-12 h-32 w-32 rounded-full border-[18px] border-secondary/15" aria-hidden="true"></div>
                        <span class="relative flex h-16 w-16 items-center justify-center rounded-xl border border-white/60 bg-surface/85 text-4xl shadow-sm" aria-hidden="true">{{ $courseIcon }}</span>
                        <span class="relative rounded-full bg-surface/90 px-3 py-1 text-xs font-bold text-primary shadow-xs">{{ $course['code'] }}</span>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-heading text-xl font-bold transition group-hover:text-primary">{{ $course['title'] }}</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($course['is_recommended'])<x-ui.badge variant="info">Recomendada para vos</x-ui.badge>@endif
                            @if ($course['is_experience'])<x-ui.badge>Experiencia interactiva</x-ui.badge>@endif
                            <x-ui.badge :variant="$statusVariant">{{ $status->label() }}</x-ui.badge>
                        </div>
                    </div>

                    <p class="mt-3 flex-1 text-sm leading-6 text-text-secondary">{{ $course['description'] ?: 'Contenido de educación vial.' }}</p>
                    <p class="mt-3 text-sm font-semibold">Público: {{ $course['audience']['label'] }}</p>
                    @if ($canManage)
                        <p class="mt-1 text-xs text-text-secondary">{{ $course['audience']['classified'] }} de {{ $course['audience']['lessons'] }} lecciones con etapa definida.</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-text-secondary">
                        <span>{{ $course['modality'] ? \Modules\Academic\Domain\Enums\CourseModality::from($course['modality'])->label() : 'Modalidad flexible' }}</span>
                        @if ($course['duration_hours'])<span>⏱ {{ $course['duration_hours'] }} hora{{ $course['duration_hours'] === 1 ? '' : 's' }}</span>@endif
                    </div>

                    @if ($course['progress'])
                        <div class="mt-4">
                            <div class="mb-1 flex justify-between text-xs text-text-secondary"><span>Tu avance</span><strong>{{ $percentage }}%</strong></div>
                            <div class="h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full bg-primary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-2">
                        @if ($canManage)
                            <a href="{{ route('courses.preview', $course['id']) }}" class="inline-flex min-h-[44px] items-center rounded-md bg-primary px-4 text-sm font-semibold text-white hover:bg-secondary">
                                Revisar curso completo
                            </a>
                        @elseif ($course['enrollment'])
                            <a href="{{ route('courses.learn', $course['enrollment']['id']) }}" class="inline-flex min-h-[44px] items-center rounded-md bg-primary px-4 text-sm font-semibold text-white hover:bg-secondary">
                                {{ $percentage === 100 ? 'Revisar misión' : ($percentage > 0 ? 'Continuar misión' : 'Comenzar misión') }}
                            </a>
                        @else
                            <a href="{{ route('courses.show', $course['id']) }}" class="inline-flex min-h-[44px] items-center rounded-md bg-primary px-4 text-sm font-semibold text-white hover:bg-secondary">Ver contenido</a>
                        @endif
                        @if ($canManage)
                            @if ($status === \Modules\Academic\Domain\Enums\CourseStatus::Draft)
                                <form method="POST" action="{{ route('courses.submitForReview', $course['id']) }}">@csrf<x-ui.button type="submit" variant="secondary" size="sm">Enviar a revisión</x-ui.button></form>
                            @endif
                            @if ($status === \Modules\Academic\Domain\Enums\CourseStatus::UnderReview)
                                <form method="POST" action="{{ route('courses.approve', $course['id']) }}">@csrf<x-ui.button type="submit" variant="secondary" size="sm">Aprobar</x-ui.button></form>
                            @endif
                            @if ($status === \Modules\Academic\Domain\Enums\CourseStatus::Approved)
                                <form method="POST" action="{{ route('courses.publish', $course['id']) }}">@csrf<x-ui.button type="submit" variant="secondary" size="sm">Publicar</x-ui.button></form>
                            @endif
                            @if (! $status->isArchived())
                                <form method="POST" action="{{ route('courses.archive', $course['id']) }}" onsubmit="return confirm('¿Seguro que querés archivar este curso? No se puede deshacer.');">@csrf<x-ui.button type="submit" variant="danger" size="sm">Archivar</x-ui.button></form>
                            @endif
                        @endif
                    </div>
                    </div>
                </article>
            @empty
                <x-ui.card><p class="text-sm text-text-secondary">Todavía no hay cursos registrados.</p></x-ui.card>
            @endforelse
        </div>
    </div>
</x-layouts.app>
