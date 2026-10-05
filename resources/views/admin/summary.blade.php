<x-layouts.app title="EDUDRIVE — Resumen del sistema">
    <div class="space-y-6">
        <div class="campus-heading flex flex-wrap items-start justify-between gap-4">
            <div><h1 class="font-heading text-2xl font-bold">Resumen del sistema</h1><p class="mt-1 text-sm text-text-secondary">Indicadores generales de operación de EDUDRIVE.</p></div>
            <div class="flex gap-2">
                <a href="{{ route('admin.system.operations') }}" class="inline-flex min-h-[44px] items-center rounded-sm border border-border px-4 text-sm font-medium hover:bg-background">Salud y auditoría</a>
                <a href="{{ route('admin.system.settings') }}" class="inline-flex min-h-[44px] items-center rounded-sm border border-border px-4 text-sm font-medium hover:bg-background">Configuración</a>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                'total_users' => 'Usuarios',
                'total_enrollments' => 'Matrículas',
                'total_achievements_granted' => 'Logros otorgados',
                'total_certificates_issued' => 'Certificados',
                'total_simulation_sessions' => 'Simulaciones',
            ] as $key => $label)
                <x-ui.card>
                    <p class="text-sm text-text-secondary">{{ $label }}</p>
                    <p class="mt-2 font-heading text-3xl font-bold">{{ number_format($summary[$key]) }}</p>
                </x-ui.card>
            @endforeach
        </div>
        <section class="rounded-lg border border-border bg-surface p-5" aria-labelledby="quality-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Control editorial</p><h2 id="quality-title" class="mt-1 font-heading text-xl font-bold">Calidad pedagógica de las lecciones</h2></div>
                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $quality['percentage'] === 100 ? 'bg-success/10 text-success-text' : 'bg-warning/10 text-warning-text' }}">{{ $quality['percentage'] }}% listas</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-text-secondary">Revisa trazabilidad, fuente editorial, duración, práctica con decisión y profundidad mínima. No reemplaza la revisión humana pedagógica, legal y de accesibilidad.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-background p-3"><p class="font-heading text-2xl font-bold">{{ $quality['total'] }}</p><p class="text-xs text-text-secondary">Lecciones revisadas</p></div>
                <div class="rounded-lg bg-success/10 p-3"><p class="font-heading text-2xl font-bold text-success-text">{{ $quality['ready'] }}</p><p class="text-xs text-text-secondary">Cumplen controles automáticos</p></div>
                <div class="rounded-lg bg-warning/10 p-3"><p class="font-heading text-2xl font-bold text-warning-text">{{ count($quality['issues']) }}</p><p class="text-xs text-text-secondary">Requieren revisión</p></div>
            </div>
            @if ($quality['reason_counts'] !== [])
                <div class="mt-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-text-secondary">Problemas más frecuentes</p>
                    <div class="mt-2 flex flex-wrap gap-2">@foreach ($quality['reason_counts'] as $reason => $count)<span class="rounded-full bg-warning/10 px-3 py-1 text-xs font-medium text-warning-text">{{ $reason }} · {{ $count }}</span>@endforeach</div>
                </div>
            @endif
            @if ($quality['issues'] !== [])
                <details class="mt-4 rounded-lg border border-border bg-background p-4">
                    <summary class="cursor-pointer text-sm font-bold text-primary">Ver lecciones que requieren revisión</summary>
                    <ul class="mt-3 space-y-3">
                        @foreach (array_slice($quality['issues'], 0, 25) as $issue)
                            <li class="rounded-md border border-border bg-surface p-3 text-sm"><a href="{{ route('courses.show', $issue['course_id']) }}" class="ed-enlace font-bold">{{ $issue['course_code'] }} · {{ $issue['course'] }}</a><span class="mt-1 block font-medium text-text">{{ $issue['lesson'] }}</span><span class="mt-1 block text-xs text-text-secondary">{{ implode(' · ', $issue['reasons']) }}</span></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    </div>
</x-layouts.app>
