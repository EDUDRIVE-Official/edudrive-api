<x-layouts.app title="EDUDRIVE — Mi progreso">
    @php $progress = $experience['total_points'] % 100; @endphp
    <div class="flex flex-col gap-8">
        @if (session('status'))<div class="rounded-lg border border-success/30 bg-success/10 p-4 text-sm font-medium text-success" role="status">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm font-medium text-danger-text" role="alert">{{ session('error') }}</div>@endif
        <section class="campus-hero relative overflow-hidden rounded-xl bg-hero px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="progress-title">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-secondary/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-accent/20" aria-hidden="true"></div>
            <div class="relative grid gap-7 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Un recorrido propio</p>
                    <h1 id="progress-title" class="mt-2 font-heading text-3xl font-bold sm:text-4xl">Mi progreso y reconocimientos</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85">Reconocemos constancia, práctica y aprendizaje; nunca velocidad ni competencia con otras personas.</p>
                    <div class="mt-6 max-w-xl">
                        <div class="flex items-end justify-between gap-3"><p class="text-sm font-semibold text-white/75">Avance hacia la siguiente etapa</p><p class="font-heading text-lg font-bold">{{ $progress }}%</p></div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-white/20" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-accent" style="width: {{ $progress }}%"></div></div>
                        <p class="mt-2 text-xs text-white/65">{{ 100 - $progress }} XP para consolidar la siguiente etapa.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4 lg:grid-cols-2">
                    <div class="rounded-xl bg-white/10 px-4 py-3"><p class="font-heading text-2xl font-bold">{{ $experience['general_level'] }}</p><p class="text-xs text-white/70">Etapa de experiencia</p></div>
                    <div class="rounded-xl bg-white/10 px-4 py-3"><p class="font-heading text-2xl font-bold text-accent">{{ $experience['total_points'] }} XP</p><p class="text-xs text-white/70">Experiencia acumulada</p></div>
                    <div class="rounded-xl bg-white/10 px-4 py-3"><p class="font-heading text-2xl font-bold">{{ count($earnedAchievements) }}</p><p class="text-xs text-white/70">Logros</p></div>
                    <div class="rounded-xl bg-white/10 px-4 py-3"><p class="font-heading text-2xl font-bold text-accent">{{ count($earnedBadges) }}</p><p class="text-xs text-white/70">Insignias</p></div>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-primary/30 bg-primary/5 p-4" aria-label="Cómo funciona el reconocimiento">
            <p class="text-xs font-bold uppercase tracking-wide text-primary">Progreso con propósito</p>
            <div class="mt-3 grid gap-3 text-sm md:grid-cols-3">
                <div><p class="flex items-center gap-2 font-bold text-text"><x-ui.icon name="book" size="sm" />Aprender</p><p class="mt-1 leading-6 text-text-secondary">Completá experiencias y explicá decisiones seguras.</p></div>
                <div><p class="flex items-center gap-2 font-bold text-text"><x-ui.icon name="progress" size="sm" />Aplicar</p><p class="mt-1 leading-6 text-text-secondary">Llevá habilidades a prácticas apropiadas para tu edad.</p></div>
                <div><p class="flex items-center gap-2 font-bold text-text"><x-ui.icon name="edit" size="sm" />Reflexionar</p><p class="mt-1 leading-6 text-text-secondary">Reconocé qué aprendiste y qué cambiarás la próxima vez.</p></div>
            </div>
            <p class="mt-3 border-t border-primary/20 pt-3 text-xs leading-5 text-text-secondary">No hay tabla de posiciones. Los puntos ayudan a visualizar tu continuidad y no sustituyen la evidencia del Pasaporte Vial.</p>
        </aside>

        <aside class="rounded-xl border border-safety/40 bg-safety/10 p-5 shadow-sm" aria-labelledby="next-step-title">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                <div><p class="text-xs font-bold uppercase tracking-wide text-warning-text">{{ $nextStep['eyebrow'] }}</p><h2 id="next-step-title" class="mt-1 font-heading text-xl font-bold text-text">{{ $nextStep['title'] }}</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-text-secondary">{{ $nextStep['text'] }}</p></div>
                <a href="{{ $nextStep['url'] }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md bg-primary px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-secondary">{{ $nextStep['label'] }} →</a>
            </div>
        </aside>

        <section aria-labelledby="experience-title">
            @if (count($experience['competencies']) > 0)
                <x-ui.card>
                    <div class="mb-4"><p class="text-xs font-bold uppercase tracking-wide text-primary">Mapa personal</p><h2 id="experience-title" class="mt-1 font-heading text-xl font-bold">Experiencia por competencia</h2><p class="mt-1 text-sm text-text-secondary">Cada competencia avanza de manera independiente según las experiencias que completás.</p></div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($experience['competencies'] as $competency)
                            <div class="rounded-lg border border-border bg-background p-4">
                                <p class="font-sans text-sm font-medium text-text">{{ $competency['competency_title'] }}</p>
                                <p class="font-heading font-bold">Nivel {{ $competency['level'] }} · {{ $competency['total_points'] }} XP</p>
                                @php $competencyProgress = $competency['total_points'] % 100; @endphp
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Avance en {{ $competency['competency_title'] }}" aria-valuenow="{{ $competencyProgress }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-primary" style="width: {{ $competencyProgress }}%"></div></div>
                                <p class="mt-1 text-xs text-text-secondary">{{ 100 - $competencyProgress }} XP para consolidar la siguiente etapa</p>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @else
                <div class="rounded-xl border border-dashed border-border bg-surface p-6 text-center"><p class="flex justify-center" aria-hidden="true"><x-ui.icon name="progress" size="lg" /></p><h2 id="experience-title" class="mt-2 font-heading font-bold">Tu mapa de competencias comenzará aquí</h2><p class="mt-1 text-sm text-text-secondary">Al completar experiencias aparecerán las áreas viales que estás desarrollando.</p></div>
            @endif
        </section>

        <section aria-labelledby="achievements-title">
            <h2 id="achievements-title" class="mb-3 font-heading text-xl font-bold">Mis logros</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($earnedAchievements as $achievement)
                    <article class="relative overflow-hidden rounded-xl border border-success/30 bg-surface p-5 shadow-sm"><div class="absolute -right-7 -top-7 h-24 w-24 rounded-full bg-success/10" aria-hidden="true"></div><div class="relative flex items-start gap-4"><div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-success/10 text-2xl" aria-hidden="true">🏆</div><div><x-ui.badge variant="success">Logro obtenido</x-ui.badge><h3 class="mt-2 font-heading text-lg font-bold">{{ $achievement['name'] }}</h3><p class="mt-1 text-sm text-text-secondary">{{ $achievement['description'] }}</p><p class="mt-2 text-xs text-text-secondary">Obtenido: {{ $achievement['earned_at_label'] }}</p></div></div></article>
                @empty
                    <div class="rounded-xl border border-dashed border-border bg-surface p-6 text-center md:col-span-2"><p class="flex justify-center" aria-hidden="true"><x-ui.icon name="certificate" size="lg" /></p><p class="mt-2 text-sm text-text-secondary">Todavía no desbloqueaste logros.</p></div>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="badges-title">
            <h2 id="badges-title" class="mb-3 font-heading text-xl font-bold">Mis insignias</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($earnedBadges as $badge)
                    <x-ui.card class="border border-primary/20">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-primary/15 bg-primary/10 text-2xl text-primary">★</div>
                            @if ($badge['level_label'])<x-ui.badge>{{ $badge['level_label'] }}</x-ui.badge>@endif
                        </div>
                        <h3 class="mt-3 font-heading text-lg font-bold">{{ $badge['name'] }}</h3>
                        <p class="font-sans text-sm text-text-secondary">{{ $badge['description'] }}</p>
                    </x-ui.card>
                @empty
                    <x-ui.card><p class="font-sans text-sm text-text-secondary">Todavía no recibiste insignias.</p></x-ui.card>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="challenges-title">
            <h2 id="challenges-title" class="mb-3 font-heading text-xl font-bold">Mis retos</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($participations as $participation)
                    <x-ui.card>
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-heading text-lg font-bold">{{ $participation['name'] }}</h3>
                            <x-ui.badge :variant="$participation['status'] === 'completed' ? 'success' : 'info'">
                                {{ $participation['status'] === 'completed' ? 'Completado' : 'En curso' }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-2 font-sans text-sm text-text-secondary">Recompensa: {{ $participation['reward'] }}</p>
                    </x-ui.card>
                @empty
                    <x-ui.card><p class="font-sans text-sm text-text-secondary">Todavía no participás en retos.</p></x-ui.card>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="catalog-title">
            <h2 id="catalog-title" class="mb-3 font-heading text-xl font-bold">Qué podés conseguir</h2>
            <x-ui.card>
                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <h3 class="font-heading font-bold">Logros disponibles</h3>
                        <ul class="mt-2 space-y-2 font-sans text-sm">
                            @forelse ($availableAchievements as $achievement)<li><strong>{{ $achievement['name'] }}</strong><br><span class="text-text-secondary">{{ $achievement['description'] }}</span></li>@empty<li class="text-text-secondary">Sin logros disponibles.</li>@endforelse
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold">Insignias disponibles</h3>
                        <ul class="mt-2 space-y-2 font-sans text-sm">
                            @forelse ($availableBadges as $badge)<li><strong>{{ $badge['name'] }}</strong><br><span class="text-text-secondary">{{ $badge['criteria'] }}</span></li>@empty<li class="text-text-secondary">Sin insignias disponibles.</li>@endforelse
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold">Retos disponibles</h3>
                        <div class="mt-2 space-y-3 font-sans text-sm">
                            @forelse ($availableChallenges as $challenge)
                                <article class="rounded-lg border border-border bg-background p-3">
                                    <h4 class="font-bold text-text">{{ $challenge['name'] }}</h4>
                                    <p class="mt-1 leading-5 text-text-secondary">{{ $challenge['description'] }}</p>
                                    <p class="mt-2 text-xs text-text-secondary">Del {{ $challenge['starts_at_label'] }} al {{ $challenge['ends_at_label'] }}</p>
                                    <p class="mt-1 text-xs"><strong>Reconocimiento:</strong> {{ $challenge['reward'] }}</p>
                                    @if ($challenge['already_joined'])
                                        <span class="mt-3 inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success-text">Ya participás</span>
                                    @elseif ($challenge['can_join'])
                                        <form method="POST" action="{{ route('gamification.challenges.join', $challenge['id']) }}" class="mt-3">
                                            @csrf
                                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary">Unirme a este reto</button>
                                        </form>
                                    @else
                                        <span class="mt-3 inline-flex rounded-full bg-safety/10 px-3 py-1 text-xs font-bold text-warning-text">Fuera del periodo de participación</span>
                                    @endif
                                </article>
                            @empty
                                <p class="text-text-secondary">Sin retos disponibles.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </section>
    </div>
</x-layouts.app>
