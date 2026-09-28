<x-layouts.app :title="'EDUDRIVE — '.$course['title']">
    @php
        $moduleCount = count($course['modules']);
        $unitCount = collect($course['modules'])->sum(fn (array $module): int => count($module['units']));
        $lessonCount = collect($lessonsByUnit)->sum(fn ($lessons): int => count($lessons));
        $courseText = mb_strtolower($course['title'].' '.$course['code']);
        $courseIcon = match (true) {
            str_contains($courseText, 'cicli'), str_contains($courseText, 'bici') => '🚲',
            str_contains($courseText, 'peat'), str_contains($courseText, 'cruce') => '🚶',
            str_contains($courseText, 'moto') => '🏍️',
            str_contains($courseText, 'niñ'), str_contains($courseText, 'escuel') => '🎒',
            str_contains($courseText, 'emerg'), str_contains($courseText, 'incident') => '🆘',
            str_contains($courseText, 'ambiente'), str_contains($courseText, 'sosten') => '🌱',
            str_contains($courseText, 'pasaj') => '🚌',
            default => '🛣️',
        };
    @endphp
    <div class="course-campus flex flex-col gap-7">
        <a href="{{ route('courses.index') }}" class="inline-flex min-h-11 items-center self-start rounded-md px-2 text-sm font-semibold text-primary hover:bg-primary/10">← Volver a cursos</a>

        <section class="campus-hero relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="course-title">
            <div class="absolute -right-14 -top-20 h-56 w-56 rounded-full bg-[#008a78]/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-28 h-48 w-48 rounded-full bg-[#f5b700]/20" aria-hidden="true"></div>
            <div class="relative grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wide">{{ $course['code'] }}</span>
                        <span class="rounded-full bg-[#5bd6c0]/20 px-3 py-1 text-xs font-bold text-[#9ce8da]">{{ \Modules\Academic\Domain\Enums\CourseStatus::from($course['status'])->label() }}</span>
                    </div>
                    <h1 id="course-title" class="mt-4 max-w-3xl font-heading text-3xl font-bold sm:text-4xl">{{ $course['title'] }}</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-white/85">{{ ($course['description'] ?? '') ?: 'Una experiencia para desarrollar conocimientos, practicar decisiones y fortalecer una convivencia vial más segura.' }}</p>
                </div>
                <img src="{{ asset('brand/mobility-campus.svg') }}" class="campus-hero-art" alt="" aria-hidden="true">
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Información general del curso">
            <div class="rounded-lg border border-border bg-surface p-4"><p class="text-xs font-semibold text-text-secondary">Módulos</p><p class="mt-1 font-heading text-2xl font-bold text-primary">{{ $moduleCount }}</p></div>
            <div class="rounded-lg border border-border bg-surface p-4"><p class="text-xs font-semibold text-text-secondary">Unidades</p><p class="mt-1 font-heading text-2xl font-bold text-secondary">{{ $unitCount }}</p></div>
            <div class="rounded-lg border border-border bg-surface p-4"><p class="text-xs font-semibold text-text-secondary">Lecciones</p><p class="mt-1 font-heading text-2xl font-bold">{{ $lessonCount }}</p></div>
            <div class="rounded-lg border border-border bg-surface p-4"><p class="text-xs font-semibold text-text-secondary">Duración estimada</p><p class="mt-1 font-heading text-lg font-bold text-warning-text">{{ ! empty($course['duration_hours']) ? $course['duration_hours'].' h' : 'Flexible' }}</p></div>
        </section>

        @if (session('error'))
            <p class="rounded-md border border-danger-text bg-surface px-4 py-3 text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-5 rounded-xl border border-secondary/35 bg-secondary/5 p-5 shadow-sm">
            <div>
                @if ($canManage)
                    <h2 class="font-heading text-lg font-bold">Vista completa para superadministración</h2>
                    <p class="mt-1 text-sm text-text-secondary">Podés revisar todos los módulos y lecciones sin restricciones ni cambios en el progreso.</p>
                @elseif ($enrollment)
                    <h2 class="font-heading text-lg font-bold">Ya estás inscrito</h2>
                    <p class="mt-1 text-sm text-text-secondary">Continúa desde donde dejaste tu aprendizaje.</p>
                @elseif (\Modules\Academic\Domain\Enums\CourseStatus::from($course['status'])->isPublished())
                    <h2 class="font-heading text-lg font-bold">¿Listo para comenzar?</h2>
                    <p class="mt-1 text-sm text-text-secondary">La inscripción es inmediata y podrás registrar tu progreso.</p>
                @else
                    <h2 class="font-heading text-lg font-bold">Inscripción no disponible</h2>
                    <p class="mt-1 text-sm text-text-secondary">Este curso estará disponible cuando sea publicado.</p>
                @endif
            </div>
            @if ($canManage)
                <a href="{{ route('courses.preview', $course['id']) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-primary px-5 font-bold text-white shadow-sm hover:bg-secondary">Abrir todas las lecciones →</a>
            @elseif ($enrollment)
                <a href="{{ route('courses.learn', $enrollment['id']) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-primary px-5 font-bold text-white shadow-sm hover:bg-secondary">Entrar al curso →</a>
            @elseif (\Modules\Academic\Domain\Enums\CourseStatus::from($course['status'])->isPublished())
                <form method="POST" action="{{ route('courses.enroll', $course['id']) }}">
                    @csrf
                    <x-ui.button type="submit">Inscribirme</x-ui.button>
                </form>
            @endif
        </div>

        @forelse ($course['modules'] as $module)
            <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm">
                <div class="border-b border-border bg-gradient-to-r from-primary/10 via-background to-secondary/10 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-text-secondary">Módulo {{ $module['position'] }}</p>
                    <h2 class="mt-1 font-heading text-xl font-bold text-text">{{ $module['title'] }}</h2>
                    <p class="mt-2 text-sm text-text-secondary">{{ $module['description'] }}</p>
                </div>

                <div class="flex flex-col divide-y divide-border">
                    @foreach ($module['units'] as $unit)
                        <article class="px-5 py-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium text-primary">Unidad {{ $unit['position'] }} · {{ $unit['code'] }}</p>
                                    <h3 class="mt-1 font-heading text-lg font-semibold text-text">{{ $unit['title'] }}</h3>
                                    <p class="mt-1 text-sm text-text-secondary">{{ $unit['description'] }}</p>
                                </div>
                                @if ($unit['duration_minutes'])
                                    <span class="rounded-full bg-background px-3 py-1 text-xs text-text-secondary">{{ $unit['duration_minutes'] }} min</span>
                                @endif
                            </div>

                            <div class="mt-4 flex flex-col gap-3">
                                @forelse ($lessonsByUnit[$unit['id']] ?? [] as $lesson)
                                    <details class="rounded-md border border-border bg-background p-4" @if ($loop->first) open @endif>
                                        <summary class="cursor-pointer font-medium text-text">{{ $lesson['title'] }}</summary>
                                        @if ($lesson['summary'])
                                            <p class="mt-3 text-sm text-text-secondary">{{ $lesson['summary'] }}</p>
                                        @endif
                                        <div class="mt-4 flex flex-col gap-3">
                                            @foreach ($lesson['blocks'] as $block)
                                                @if ($block['type'] === 'text')
                                                    <div class="rounded-md bg-surface p-4 text-sm leading-6 text-text">{!! \Illuminate\Support\Str::markdown($block['payload']['markdown'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                                @elseif ($block['type'] === 'scenario')
                                                    @include('courses.blocks.scenario', ['block' => $block])
                                                @else
                                                    <div class="rounded-md bg-surface p-4 text-sm text-text-secondary">Contenido {{ $block['type'] }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </details>
                                @empty
                                    <p class="rounded-md bg-background p-4 text-sm text-text-secondary">Esta unidad todavía no tiene lecciones.</p>
                                @endforelse
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-lg border border-border bg-surface p-6 text-text-secondary">Este curso todavía no tiene módulos.</div>
        @endforelse
    </div>
</x-layouts.app>
