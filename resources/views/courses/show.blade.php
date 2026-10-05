<x-layouts.app :title="'EDUDRIVE — '.$course['title']">
    @php
        $moduleCount = count($course['modules']);
        $unitCount = collect($course['modules'])->sum(fn (array $module): int => count($module['units']));
        $lessonCount = collect($lessonsByUnit)->sum(fn ($lessons): int => count($lessons));
        $courseStatus = \Modules\Academic\Domain\Enums\CourseStatus::from($course['status']);
    @endphp
    <div class="course-campus flex flex-col gap-7">
        <a href="{{ route('courses.index') }}" class="ed-enlace self-start"><x-ui.icon name="back" size="sm" />Volver a cursos</a>

        <section class="campus-hero px-6 py-8 text-white sm:px-8" aria-labelledby="course-title">
            <div class="relative grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="ed-codigo rounded-full border-2 border-white px-3 py-1 !text-white">{{ $course['code'] }}</span>
                        <span class="ed-estado ed-estado--practiced">{{ $courseStatus->label() }}</span>
                    </div>
                    <h1 id="course-title" class="mt-4 max-w-3xl">{{ $course['title'] }}</h1>
                    <p class="mt-4 max-w-2xl text-lg leading-7 text-white/85">{{ ($course['description'] ?? '') ?: 'Una experiencia para desarrollar conocimientos, practicar decisiones y fortalecer una convivencia vial más segura.' }}</p>
                </div>
                <img src="{{ asset('brand/mobility-campus.svg') }}" class="campus-hero-art" alt="" aria-hidden="true">
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Información general del curso">
            <div class="ed-dato"><p class="ed-dato-rotulo">Módulos</p><p class="ed-dato-cifra">{{ $moduleCount }}</p></div>
            <div class="ed-dato"><p class="ed-dato-rotulo">Unidades</p><p class="ed-dato-cifra">{{ $unitCount }}</p></div>
            <div class="ed-dato"><p class="ed-dato-rotulo">Lecciones</p><p class="ed-dato-cifra">{{ $lessonCount }}</p></div>
            <div class="ed-dato"><p class="ed-dato-rotulo">Duración estimada</p><p class="ed-dato-cifra">{{ ! empty($course['duration_hours']) ? $course['duration_hours'].' h' : 'Flexible' }}</p></div>
        </section>

        @if (session('error'))
            <p class="ed-aviso text-lg font-semibold text-danger-text"><x-ui.icon name="warning" />{{ session('error') }}</p>
        @endif

        <div class="campus-card flex flex-wrap items-center justify-between gap-5">
            <div>
                @if ($canManage)
                    <h2 class="text-3xl">Vista completa para superadministración</h2>
                    <p class="mt-1 text-lg text-text-secondary">Podés revisar todos los módulos y lecciones sin restricciones ni cambios en el progreso.</p>
                @elseif ($enrollment)
                    <h2 class="text-3xl">Ya estás inscrito</h2>
                    <p class="mt-1 text-lg text-text-secondary">Continúa desde donde dejaste tu aprendizaje.</p>
                @elseif ($courseStatus->isPublished())
                    <h2 class="text-3xl">¿Listo para comenzar?</h2>
                    <p class="mt-1 text-lg text-text-secondary">La inscripción es inmediata y podrás registrar tu progreso.</p>
                @else
                    <h2 class="text-3xl">Inscripción no disponible</h2>
                    <p class="mt-1 text-lg text-text-secondary">Este curso estará disponible cuando sea publicado.</p>
                @endif
            </div>
            @if ($canManage)
                <a href="{{ route('courses.preview', $course['id']) }}" class="ed-btn">Abrir todas las lecciones →</a>
            @elseif ($enrollment)
                <a href="{{ route('courses.learn', $enrollment['id']) }}" class="ed-btn">Entrar al curso →</a>
            @elseif ($courseStatus->isPublished())
                <form method="POST" action="{{ route('courses.enroll', $course['id']) }}">
                    @csrf
                    <x-ui.button type="submit" size="lg">Inscribirme</x-ui.button>
                </form>
            @endif
        </div>

        @forelse ($course['modules'] as $module)
            <section class="overflow-hidden rounded-md border-[3px] border-border-strong bg-surface">
                <div class="bg-hero px-5 py-5 text-hero-text">
                    <p class="text-lg font-semibold text-white/80">Módulo {{ $module['position'] }}</p>
                    <h2 class="mt-1 text-4xl">{{ $module['title'] }}</h2>
                    <p class="mt-2 text-lg text-white/85">{{ $module['description'] }}</p>
                </div>
                <span class="ed-cebra" aria-hidden="true"></span>

                <div class="flex flex-col divide-y-[3px] divide-border-strong">
                    @foreach ($module['units'] as $unit)
                        <article class="px-5 py-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-lg font-bold text-text-secondary">Unidad {{ $unit['position'] }} · {{ $unit['code'] }}</p>
                                    <h3 class="mt-1 text-3xl">{{ $unit['title'] }}</h3>
                                    <p class="mt-1 text-lg text-text-secondary">{{ $unit['description'] }}</p>
                                </div>
                                @if ($unit['duration_minutes'])
                                    <span class="ed-estado"><x-ui.icon name="clock" size="sm" />{{ $unit['duration_minutes'] }} min</span>
                                @endif
                            </div>

                            <div class="mt-4 flex flex-col gap-3">
                                @forelse ($lessonsByUnit[$unit['id']] ?? [] as $lesson)
                                    <details class="ed-detalle" @if ($loop->first) open @endif>
                                        <summary>{{ $lesson['title'] }}</summary>
                                        @if ($lesson['summary'])
                                            <p class="mt-3 text-lg text-text-secondary">{{ $lesson['summary'] }}</p>
                                        @endif
                                        <div class="mt-4 flex flex-col gap-3">
                                            @foreach ($lesson['blocks'] as $block)
                                                @if ($block['type'] === 'text')
                                                    <div class="rounded-sm bg-surface p-4 text-lg leading-7 text-text">{!! \Illuminate\Support\Str::markdown($block['payload']['markdown'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                                @elseif ($block['type'] === 'scenario')
                                                    @include('courses.blocks.scenario', ['block' => $block])
                                                @else
                                                    <div class="rounded-sm bg-surface p-4 text-lg text-text-secondary">Contenido {{ $block['type'] }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </details>
                                @empty
                                    <p class="ed-vacio text-lg text-text-secondary">Esta unidad todavía no tiene lecciones.</p>
                                @endforelse
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="ed-vacio text-lg text-text-secondary">Este curso todavía no tiene módulos.</div>
        @endforelse
    </div>
</x-layouts.app>
