<x-layouts.app title="EDUDRIVE — Mis certificados">
    @php
        $certificateCollection = collect($certificates);
        $validCount = $certificateCollection->filter(function (array $certificate): bool {
            $expired = ! empty($certificate['expires_at']) && new DateTimeImmutable($certificate['expires_at']) < new DateTimeImmutable();
            return $certificate['status'] !== 'revoked' && ! $expired;
        })->count();
    @endphp
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <section class="campus-hero relative overflow-hidden rounded-xl bg-hero px-6 py-8 text-white shadow-md sm:px-8" aria-labelledby="certificates-title">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-secondary/55" aria-hidden="true"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-accent/20" aria-hidden="true"></div>
            <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Aprendizajes alcanzados</p>
                    <h1 id="certificates-title" class="mt-2 font-heading text-3xl font-bold sm:text-4xl">Mis certificados</h1>
                    <p class="mt-3 text-sm leading-6 text-white/85">Credenciales verificables de las experiencias que completaste. Complementan tu Pasaporte Vial y no sustituyen licencias oficiales.</p>
                </div>
                <div class="flex shrink-0 gap-3 text-center">
                    <div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold">{{ count($certificates) }}</p><p class="text-xs text-white/70">Emitidos</p></div>
                    <div class="rounded-xl bg-white/10 px-5 py-3"><p class="font-heading text-3xl font-bold text-accent">{{ $validCount }}</p><p class="text-xs text-white/70">Vigentes</p></div>
                </div>
            </div>
        </section>

        @forelse ($certificates as $certificate)
            @php
                $isExpired = $certificate['expires_at'] && new DateTimeImmutable($certificate['expires_at']) < new DateTimeImmutable();
                $effectiveStatus = $certificate['status'] === 'revoked' ? 'revoked' : ($isExpired ? 'expired' : 'valid');
                $statusLabels = ['valid' => 'Válido', 'expired' => 'Vencido', 'revoked' => 'Revocado'];
                $statusVariants = ['valid' => 'success', 'expired' => 'warning', 'revoked' => 'danger'];
            @endphp
            <article class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm transition hover:shadow-md">
                <div class="h-2 {{ $effectiveStatus === 'valid' ? 'bg-secondary' : ($effectiveStatus === 'expired' ? 'bg-warning' : 'bg-danger') }}" aria-hidden="true"></div>
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-2xl" aria-hidden="true"><x-ui.icon name="certificate" size="lg" /></div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wide text-primary">Certificado de aprendizaje vial</span>
                                <h2 class="mt-1 font-heading text-xl font-bold">{{ $certificate['course_title'] ?? $certificate['course_id'] }}</h2>
                                <p class="mt-2 text-sm text-text-secondary">Emitido: {{ $certificate['issued_at'] }} · Vence: {{ $certificate['expires_at'] ?? 'Sin vencimiento' }}</p>
                            </div>
                        </div>
                        <x-ui.badge :variant="$statusVariants[$effectiveStatus]">{{ $statusLabels[$effectiveStatus] }}</x-ui.badge>
                    </div>
                    <div class="mt-5 flex flex-col justify-between gap-4 rounded-lg border border-border bg-background p-4 sm:flex-row sm:items-center">
                        <div><span class="text-xs font-bold uppercase tracking-wide text-text-secondary">Código verificable</span><p class="mt-1 font-heading text-lg font-bold tracking-wider">{{ $certificate['validation_code'] }}</p></div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('certificates.verify', ['code' => $certificate['validation_code']]) }}" class="inline-flex min-h-11 items-center rounded-md border border-primary px-4 py-2 text-sm font-bold text-primary hover:bg-primary/5">Validar</a>
                            <a href="{{ route('certificates.show', $certificate['id']) }}" class="inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary">Abrir certificado →</a>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-border bg-surface p-8 text-center"><div class="flex justify-center" aria-hidden="true"><x-ui.icon name="certificate" size="lg" /></div><h2 class="mt-3 font-heading text-lg font-bold">Tu colección está lista para comenzar</h2><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-text-secondary">Completá todas las lecciones de un curso para registrar el aprendizaje y recibir la credencial correspondiente.</p><a href="{{ route('courses.index') }}" class="mt-4 inline-flex min-h-11 items-center rounded-md bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-secondary">Explorar cursos</a></div>
        @endforelse
    </div>
</x-layouts.app>
