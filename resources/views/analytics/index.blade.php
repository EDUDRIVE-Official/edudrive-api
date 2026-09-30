<x-layouts.app title="EDUDRIVE — Analítica">
    @php
        $labels = ['enrollments_summary' => ['Matrículas', 'Distribución por estado de las matrículas.'], 'certifications_summary' => ['Certificados', 'Certificados emitidos agrupados por estado.'], 'users_summary' => ['Usuarios', 'Cuentas registradas agrupadas por estado.']];
        $reportLabels = ['analytics.enrollments_summary' => 'Resumen de matrículas', 'analytics.certifications_summary' => 'Resumen de certificados', 'analytics.users_summary' => 'Resumen de usuarios'];
        $statusLabels = ['pending' => 'Pendiente', 'processing' => 'Procesando', 'completed' => 'Completado', 'failed' => 'Falló'];
    @endphp
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Analítica</h1><p class="mt-1 text-sm text-text-secondary">Generá reportes consolidados bajo demanda. El procesamiento ocurre en segundo plano.</p></div>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($types as $type)
                <x-ui.card class="flex flex-col justify-between gap-4">
                    <div><h2 class="font-heading text-lg font-bold">{{ $labels[$type->value][0] }}</h2><p class="mt-1 text-sm text-text-secondary">{{ $labels[$type->value][1] }}</p></div>
                    <form method="POST" action="{{ route('analytics.reports.store') }}">@csrf <input type="hidden" name="type" value="{{ $type->value }}"><x-ui.button type="submit" class="w-full">Generar reporte</x-ui.button></form>
                </x-ui.card>
            @endforeach
        </div>
        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Mis reportes recientes</h2>
            <p class="mt-1 text-sm text-text-secondary">Cada resultado solo es visible para la persona que lo generó.</p>
            <div class="mt-4 divide-y divide-border">
                @forelse ($reports as $report)
                    <a href="{{ route('analytics.reports.show', $report->id) }}" class="flex min-h-[56px] items-center justify-between gap-4 py-3 hover:text-primary">
                        <span><strong class="block">{{ $reportLabels[$report->type] ?? 'Reporte analítico' }}</strong><small class="text-text-secondary">Solicitado {{ $report->created_at->format('d/m/Y H:i') }}</small></span>
                        <x-ui.badge :variant="match ($report->status) { 'completed' => 'success', 'failed' => 'danger', default => 'warning' }">{{ $statusLabels[$report->status] ?? $report->status }}</x-ui.badge>
                    </a>
                @empty
                    <p class="py-4 text-sm text-text-secondary">Todavía no has generado reportes.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
