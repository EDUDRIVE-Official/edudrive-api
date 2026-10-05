<x-layouts.app title="EDUDRIVE — Cursos">
    @php
        $courseCollection = collect($courses);
        $enrolledCount = $courseCollection->filter(fn (array $course): bool => ! empty($course['enrollment']))->count();
        $completedCount = $courseCollection->filter(fn (array $course): bool => (int) ($course['progress']['progress_percentage'] ?? 0) === 100)->count();
        $recommendedCount = $courseCollection->where('is_recommended', true)->count();
        $stages = [['E1', 'DESCUBRO', '3–6 años'], ['E2', 'COMPRENDO', '7–12 años'], ['E3', 'DECIDO', '13–16 años'], ['E4', 'CONDUZCO', '17+ años']];
    @endphp
    <div class="course-catalog flex flex-col gap-7" x-data="{ query: '', filter: 'all' }">
        <section class="catalog-hero px-6 py-8 text-white sm:px-8" aria-labelledby="courses-title">
            <div class="relative grid items-end gap-8 md:grid-cols-[1.25fr_1fr]">
                <div class="max-w-2xl">
                    <p class="hero-eyebrow">TU PRÓXIMA DECISIÓN EMPIEZA AQUÍ</p>
                    <h1 id="courses-title" class="mt-2">Tu recorrido de educación vial</h1>
                    <p class="mt-3 max-w-xl text-lg leading-6 text-white/85">Consultá el público de cada curso y avanzá a tu ritmo. Tu práctica queda registrada en el Pasaporte Vial.</p>
                </div>
                <section class="catalog-stats ed-tablero" aria-label="Resumen de cursos">
                    <div><p class="ed-tablero-rotulo">Disponibles</p><p class="ed-tablero-cifra">{{ $courseCollection->count() }}</p></div>
                    <div><p class="ed-tablero-rotulo">En tu recorrido</p><p class="ed-tablero-cifra">{{ $enrolledCount }}</p></div>
                    <div><p class="ed-tablero-rotulo">Completados</p><p class="ed-tablero-cifra">{{ $completedCount }}</p></div>
                    <div><p class="ed-tablero-rotulo">Recomendados</p><p class="ed-tablero-cifra">{{ $recommendedCount }}</p></div>
                </section>
            </div>
        </section>

        <section class="catalog-route campus-card" aria-labelledby="learning-route-title">
            <p class="campus-eyebrow">Tu ruta EDUDRIVE</p>
            <ol class="ed-ruta" aria-label="Etapas del recorrido">
                @foreach ($stages as [$code, $name, $ages])
                    <li @if ($learnerStage['stage'] === $code) class="is-actual" aria-current="step" @endif>
                        <x-ui.stage-plate :stage="$code" />
                        <span class="ed-ruta-nombre">{{ $name }}</span>
                        <span class="ed-ruta-edad">{{ $ages }}</span>
                        @if ($learnerStage['stage'] === $code)<span class="ed-ruta-aqui">Tu etapa</span>@endif
                    </li>
                @endforeach
            </ol>
            <span class="ed-calzada" aria-hidden="true"></span>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-2">
                <h2 id="learning-route-title" class="text-3xl">{{ $learnerStage['identity'] }}</h2>
                <span class="ed-estado">{{ $learnerStage['age_range'] }}</span>
            </div>
            <p class="mt-2 text-lg leading-6 text-text-secondary">{{ $learnerStage['guidance'] }}</p>
            <p class="mt-2 text-lg">La edad orienta el recorrido; completar un curso no demuestra por sí solo una competencia.</p>
            @if ($learnerStage['stage'] === 'E4')
                <p class="mt-2 text-lg font-semibold">Propósito: {{ ['mobility' => 'Movilidad cotidiana · peatón, pasajero y ciclismo según tu elección', 'auto' => 'Automóvil', 'motorcycle' => 'Motocicleta'][$learningPurpose ?? ''] ?? 'Por elegir en Mi perfil' }}</p>
                <p class="mt-2 text-lg">Siguiente paso: diagnóstico inicial con orientación. La evaluación por competencias aún no está disponible en este recorrido.</p>
            @endif
            <a class="ed-enlace mt-2" href="{{ route('student-profile.show') }}">Revisar mi edad, propósito y apoyos</a>
            @if ($recommendedCount === 0)
                <p class="ed-aviso mt-3 text-lg text-text"><x-ui.icon name="info" />Aún no hay un curso publicado con correspondencia confirmada para tu recorrido. Tus cursos y avances se conservan. Solicitá orientación antes de elegir contenido de otra edad.</p>
            @endif
            @if ($canManage)
                <p class="mt-3 text-base">Clasificación del catálogo: {{ $courseCollection->filter(fn (array $course): bool => $course['audience']['stage'] !== null)->count() }} de {{ $courseCollection->count() }} cursos tienen una etapa única en todas sus lecciones. Esta clasificación no sustituye la revisión del contenido.</p>
            @endif
        </section>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-4xl">Explorá los cursos</h2>
                <p class="mt-1 text-lg text-text-secondary">Encontrá tu siguiente oportunidad de aprendizaje.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('courses.create') }}" class="ed-btn">Nuevo curso</a>
            @endif
        </div>

        @if (session('status'))
            <p class="font-sans text-base font-bold text-success-text" role="status">{{ session('status') }}</p>
        @endif

        @if (session('error'))
            <p class="font-sans text-base font-bold text-danger-text">{{ session('error') }}</p>
        @endif

        <section class="campus-card" aria-label="Buscar y filtrar cursos">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <div>
                    <label for="course-search" class="mb-1 block text-base font-bold text-text">Buscar por nombre o código</label>
                    <input id="course-search" type="search" x-model="query" placeholder="Ejemplo: bicicleta, peatón o convivencia" class="ed-control placeholder:text-text-secondary">
                </div>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar cursos">
                    @foreach ([['all', 'Todos'], ['recommended', 'Para vos'], ['enrolled', 'En curso'], ['completed', 'Completados']] as [$value, $label])
                        <button type="button" @click="filter = '{{ $value }}'" :aria-pressed="filter === '{{ $value }}'" :class="filter === '{{ $value }}' ? 'bg-accent text-[#14161a]' : 'bg-surface text-text hover:bg-background'" class="inline-flex min-h-12 items-center gap-2 rounded-full border-[3px] border-border-strong px-5 text-base font-bold transition">
                            <span x-show="filter === '{{ $value }}'" aria-hidden="true"><x-ui.icon name="check" size="sm" /></span>{{ $label }}
                        </button>
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
                    $coursePictogram = match (true) {
                        str_contains($titleForIcon, 'cicli'), str_contains($titleForIcon, 'bici') => 'bici',
                        str_contains($titleForIcon, 'peat'), str_contains($titleForIcon, 'cruce') => 'peaton',
                        str_contains($titleForIcon, 'moto') => 'moto',
                        str_contains($titleForIcon, 'niñ'), str_contains($titleForIcon, 'escuel') => 'escuela',
                        str_contains($titleForIcon, 'emerg'), str_contains($titleForIcon, 'incident') => 'emergencia',
                        str_contains($titleForIcon, 'ambiente'), str_contains($titleForIcon, 'sosten') => 'hoja',
                        str_contains($titleForIcon, 'pasaj'), str_contains($titleForIcon, 'bus') => 'bus',
                        str_contains($titleForIcon, 'conduc'), str_contains($titleForIcon, 'vehic'), str_contains($titleForIcon, 'estacion') => 'auto',
                        default => 'ruta',
                    };
                    $searchableCourse = mb_strtolower($course['title'].' '.$course['code'].' '.($course['description'] ?? ''));
                    $filterState = $percentage === 100 ? 'completed' : (! empty($course['enrollment']) ? 'enrolled' : 'available');
                @endphp
                <article
                    x-show="(@js($searchableCourse).includes(query.toLocaleLowerCase())) && (filter === 'all' || (filter === 'recommended' && @js((bool) $course['is_recommended'])) || filter === @js($filterState))"
                    x-transition.opacity
                    class="catalog-card group flex flex-col bg-surface p-5 {{ $course['is_recommended'] ? 'catalog-card--recomendada' : '' }} {{ $status === \Modules\Academic\Domain\Enums\CourseStatus::Draft ? 'catalog-card--borrador' : '' }}"
                >
                    <div class="flex items-center gap-4">
                        <span class="ed-pic" aria-hidden="true"><x-ui.pictogram :name="$coursePictogram" /></span>
                        <span class="ed-codigo">{{ $course['code'] }}</span>
                    </div>
                    <div class="mt-4 flex flex-wrap items-start justify-between gap-3">
                        <h2 class="text-3xl">{{ $course['title'] }}</h2>
                        <div class="flex flex-wrap gap-2">
                            @if ($course['is_recommended'])<x-ui.badge variant="info">Recomendada para vos</x-ui.badge>@endif
                            @if ($course['is_experience'])<x-ui.badge>Experiencia interactiva</x-ui.badge>@endif
                            <x-ui.badge :variant="$statusVariant">{{ $status->label() }}</x-ui.badge>
                        </div>
                    </div>

                    <p class="mt-3 flex-1 text-lg leading-6 text-text-secondary">{{ $course['description'] ?: 'Contenido de educación vial.' }}</p>
                    <p class="mt-3 text-lg font-semibold">Público: {{ $course['audience']['label'] }}</p>
                    @if ($canManage)
                        <p class="mt-1 text-base text-text-secondary">{{ $course['audience']['classified'] }} de {{ $course['audience']['lessons'] }} lecciones con etapa definida.</p>
                    @endif
                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-base font-semibold text-text-secondary">
                        <span class="inline-flex items-center gap-2"><x-ui.icon name="organization" size="sm" />{{ $course['modality'] ? \Modules\Academic\Domain\Enums\CourseModality::from($course['modality'])->label() : 'Modalidad flexible' }}</span>
                        @if ($course['duration_hours'])<span class="inline-flex items-center gap-2"><x-ui.icon name="clock" size="sm" />{{ $course['duration_hours'] }} hora{{ $course['duration_hours'] === 1 ? '' : 's' }}</span>@endif
                    </div>

                    @if ($course['progress'])
                        <div class="mt-4">
                            <div class="mb-1 flex justify-between text-base text-text-secondary"><span>Tu avance</span><strong>{{ $percentage }}%</strong></div>
                            <div class="h-3.5 overflow-hidden rounded-full border-2 border-border-strong bg-background" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100" aria-label="Tu avance en {{ $course['title'] }}">
                                <div class="h-full bg-secondary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        @if ($canManage)
                            <a href="{{ route('courses.preview', $course['id']) }}" class="ed-btn">Revisar curso completo</a>
                        @elseif ($course['enrollment'])
                            <a href="{{ route('courses.learn', $course['enrollment']['id']) }}" class="ed-btn">
                                {{ $percentage === 100 ? 'Revisar misión' : ($percentage > 0 ? 'Continuar misión' : 'Comenzar misión') }}
                            </a>
                        @else
                            <a href="{{ route('courses.show', $course['id']) }}" class="ed-btn">Ver contenido</a>
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
                </article>
            @empty
                <x-ui.card><p class="text-lg text-text-secondary">Todavía no hay cursos registrados.</p></x-ui.card>
            @endforelse
        </div>
    </div>
</x-layouts.app>
