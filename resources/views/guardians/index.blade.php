<x-layouts.app title="EDUDRIVE — Mi acompañamiento">
    @php
        $minorCollection = collect($minors);
        $totalPending = $minorCollection->sum('pending_observations_count');
        $totalRecorded = $minorCollection->sum('recorded_observations_count');
    @endphp
    <div class="flex flex-col gap-6">
        <section class="campus-hero relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="guardian-title">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[#008a78]/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-[#f5b700]/20" aria-hidden="true"></div>
            <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                <div class="max-w-2xl"><p class="text-sm font-bold uppercase tracking-[0.16em] text-[#5bd6c0]">Familia y mentoría</p><h1 id="guardian-title" class="mt-2 font-heading text-3xl font-bold sm:text-4xl">Mi acompañamiento</h1><p class="mt-3 text-sm leading-6 text-white/85">Conversá, practicá y registrá aprendizajes junto a las personas menores vinculadas. Este espacio orienta el proceso y no reemplaza la supervisión presencial.</p></div>
                <div class="grid shrink-0 grid-cols-2 gap-3 text-center"><div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold text-[#ffd45a]">{{ $totalPending }}</p><p class="text-xs text-white/70">Pendientes</p></div><div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold text-[#5bd6c0]">{{ $totalRecorded }}</p><p class="text-xs text-white/70">Registradas</p></div></div>
            </div>
        </section>

        <aside class="grid gap-3 rounded-xl border border-primary/25 bg-primary/5 p-5 text-sm sm:grid-cols-3" aria-label="Cómo acompañar de forma segura">
            <div><span class="font-bold text-text">1. Prepará</span><p class="mt-1 leading-5 text-text-secondary">Elegí un entorno permitido, protegido y sin prisa.</p></div>
            <div><span class="font-bold text-text">2. Observá</span><p class="mt-1 leading-5 text-text-secondary">Dejá que explique el riesgo y la decisión con sus palabras.</p></div>
            <div><span class="font-bold text-text">3. Conversá</span><p class="mt-1 leading-5 text-text-secondary">Reconocé lo logrado y acordá una mejora concreta.</p></div>
        </aside>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($minors as $minor)
                <article class="group rounded-xl border border-border bg-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-2xl" aria-hidden="true">🧭</div>
                        @if ($minor['pending_observations_count'] > 0)
                            <span class="rounded-full bg-warning/10 px-3 py-1 text-xs font-bold text-warning-text">
                                {{ $minor['pending_observations_count'] }} pendiente{{ $minor['pending_observations_count'] === 1 ? '' : 's' }}
                            </span>
                        @else
                            <span class="rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success-text">Al día</span>
                        @endif
                    </div>
                    <h2 class="mt-3 font-heading text-lg font-bold">{{ $minor['name'] }}</h2>
                    <p class="mt-1 text-sm text-text-secondary">{{ $minor['recorded_observations_count'] }} práctica{{ $minor['recorded_observations_count'] === 1 ? '' : 's' }} registrada{{ $minor['recorded_observations_count'] === 1 ? '' : 's' }} por vos.</p>
                    <a href="{{ route('guardians.web.show', $minor['user_id']) }}" class="mt-4 inline-flex min-h-[44px] items-center rounded-md bg-primary px-4 text-sm font-semibold text-white hover:bg-secondary">
                        {{ $minor['pending_observations_count'] > 0 ? 'Atender prácticas' : 'Ver progreso' }} →
                    </a>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-border bg-surface p-8 text-center md:col-span-2"><p class="text-4xl" aria-hidden="true">🤝</p><h2 class="mt-3 font-heading font-bold">Sin relaciones de acompañamiento</h2><p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-text-secondary">No tenés personas menores vinculadas. Una persona administradora puede crear la relación desde el módulo de usuarios.</p></div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
