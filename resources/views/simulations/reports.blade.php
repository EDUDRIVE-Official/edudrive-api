<x-layouts.app title="EDUDRIVE — Reportes de simulación">
    <div class="space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Reportes de simulación</h1><p class="mt-1 text-sm text-text-secondary">Consolidado de sesiones, telemetría, evolución y decisiones de riesgo.</p></div>

        <x-ui.card>
            <h2 class="mb-4 font-heading text-lg font-bold">Sesiones por usuario</h2>
            <x-ui.table>
                <x-slot:head><tr><th class="px-4 py-2">Usuario</th><th class="px-4 py-2">Total</th><th class="px-4 py-2">Completadas</th><th class="px-4 py-2">Canceladas</th><th class="px-4 py-2">Duración promedio</th></tr></x-slot:head>
                @forelse ($sessionReports as $report)
                    <tr><td class="px-4 py-2 text-sm"><strong>{{ $report['user_name'] }}</strong>@if ($report['user_email'])<br><span class="text-xs text-text-secondary">{{ $report['user_email'] }}</span>@endif</td><td class="px-4 py-2">{{ $report['session_count'] }}</td><td class="px-4 py-2">{{ $report['completed_count'] }}</td><td class="px-4 py-2">{{ $report['cancelled_count'] }}</td><td class="px-4 py-2">{{ $report['average_duration_minutes'] !== null ? $report['average_duration_minutes'].' min' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-text-secondary">No hay sesiones registradas.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="mb-4 font-heading text-lg font-bold">Telemetría</h2>
                @forelse ($telemetryReports as $report)
                    <div class="border-b border-border py-3 last:border-0"><p class="text-sm font-medium">{{ $report['user_name'] }}</p><p class="text-sm text-text-secondary">{{ $report['total_events'] }} eventos en {{ $report['session_count'] }} sesiones</p></div>
                @empty
                    <p class="text-sm text-text-secondary">Sin telemetría disponible.</p>
                @endforelse
            </x-ui.card>
            <x-ui.card>
                <h2 class="mb-4 font-heading text-lg font-bold">Decisiones de riesgo</h2>
                @forelse ($riskReports as $report)
                    <div class="border-b border-border py-3 last:border-0"><p class="text-sm font-medium">{{ $report['user_name'] }}</p><p class="text-sm text-text-secondary">{{ $report['appropriate_count'] }} apropiadas · {{ $report['inappropriate_count'] }} por mejorar · consistencia {{ $report['average_consistency_score'] ?? '—' }}</p></div>
                @empty
                    <p class="text-sm text-text-secondary">Sin evaluaciones de riesgo disponibles.</p>
                @endforelse
            </x-ui.card>
        </div>

        <x-ui.card>
            <h2 class="mb-4 font-heading text-lg font-bold">Evolución de resultados</h2>
            @forelse ($evolutionReports as $report)
                <div class="border-b border-border py-3 last:border-0"><p class="text-sm font-medium">{{ $report['user_name'] }}</p><p class="text-sm text-text-secondary">{{ count($report['entries']) }} resultados prácticos disponibles</p></div>
            @empty
                <p class="text-sm text-text-secondary">Aún no hay resultados suficientes para mostrar evolución.</p>
            @endforelse
        </x-ui.card>
    </div>
</x-layouts.app>
