<x-layouts.app title="EDUDRIVE — Gobierno de IA">
    <div class="space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Gobierno de IA</h1><p class="mt-1 text-sm text-text-secondary">Inventario y supervisión de componentes de inteligencia artificial registrados en EDUDRIVE.</p></div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([['Sistemas', count($systems)], ['Modelos', count($models)], ['Prompts', count($prompts)], ['Proveedores', count($providers)], ['Incidentes abiertos', collect($incidents)->where('status', '!=', 'resolved')->count()]] as [$label, $value])
                <x-ui.card><p class="text-sm text-text-secondary">{{ $label }}</p><p class="mt-1 font-heading text-3xl font-bold">{{ $value }}</p></x-ui.card>
            @endforeach
        </div>

        <x-ui.card>
            <h2 class="mb-4 font-heading text-lg font-bold">Sistemas de IA</h2>
            <x-ui.table>
                <x-slot:head><tr><th class="px-4 py-2">Sistema</th><th class="px-4 py-2">Riesgo</th><th class="px-4 py-2">Supervisión</th><th class="px-4 py-2">Estado</th><th class="px-4 py-2">Aprobaciones</th></tr></x-slot:head>
                @forelse ($systems as $system)
                    <tr><td class="px-4 py-2"><p class="font-medium">{{ $system['name'] }}</p><p class="text-xs text-text-secondary">{{ $system['purpose'] }}</p></td><td class="px-4 py-2"><x-ui.badge :variant="in_array($system['risk_level'], ['high', 'critical'], true) ? 'danger' : 'warning'">{{ ['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Crítico'][$system['risk_level']] ?? 'Sin clasificar' }}</x-ui.badge></td><td class="px-4 py-2">Nivel {{ $system['supervision_level'] }}</td><td class="px-4 py-2">{{ ['draft' => 'Borrador', 'under_review' => 'En revisión', 'approved' => 'Aprobado', 'active' => 'Activo', 'suspended' => 'Suspendido', 'retired' => 'Retirado'][$system['status']] ?? 'Sin definir' }}</td><td class="px-4 py-2 text-sm">Comité: {{ $system['committee_approved'] ? 'sí' : 'no' }} · Extraordinaria: {{ $system['extraordinary_approval_granted'] ? 'sí' : 'no' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-text-secondary">No hay sistemas de IA registrados.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card><h2 class="mb-4 font-heading text-lg font-bold">Modelos</h2>@forelse ($models as $model)<div class="border-b border-border py-3 last:border-0"><div class="flex justify-between gap-3"><div><p class="font-medium">{{ $model['name'] }} <span class="text-sm text-text-secondary">v{{ $model['version'] }}</span></p><p class="text-sm text-text-secondary">{{ $model['provider'] }} · {{ $model['use_case'] ?? 'sin caso de uso documentado' }}</p></div><x-ui.badge :variant="$model['status'] === 'approved' ? 'success' : 'warning'">{{ ['draft' => 'Borrador', 'under_review' => 'En revisión', 'approved' => 'Aprobado', 'rejected' => 'Rechazado', 'retired' => 'Retirado'][$model['status']] ?? 'Sin definir' }}</x-ui.badge></div></div>@empty<p class="text-sm text-text-secondary">No hay modelos registrados.</p>@endforelse</x-ui.card>
            <x-ui.card><h2 class="mb-4 font-heading text-lg font-bold">Evaluaciones de proveedores</h2>@forelse ($providers as $provider)<div class="border-b border-border py-3 last:border-0"><div class="flex justify-between gap-3"><div><p class="font-medium">{{ $provider['provider_name'] }}</p><p class="text-sm text-text-secondary">Ubicación de datos: {{ $provider['data_location'] }} · conservación: {{ $provider['retention_policy'] }}</p></div><x-ui.badge :variant="$provider['approval_status'] === 'approved' ? 'success' : 'warning'">{{ ['pending' => 'Pendiente', 'under_review' => 'En revisión', 'approved' => 'Aprobado', 'rejected' => 'Rechazado'][$provider['approval_status']] ?? 'Sin definir' }}</x-ui.badge></div></div>@empty<p class="text-sm text-text-secondary">No hay proveedores evaluados.</p>@endforelse</x-ui.card>
        </div>

        <x-ui.card><h2 class="mb-4 font-heading text-lg font-bold">Instrucciones controladas</h2><div class="grid gap-3 sm:grid-cols-2">@forelse ($prompts as $prompt)<div class="rounded-sm border border-border p-3"><div class="flex justify-between gap-2"><p class="font-medium">{{ $prompt['identifier'] }} · v{{ $prompt['version'] }}</p><x-ui.badge :variant="$prompt['status'] === 'approved' ? 'success' : 'warning'">{{ ['draft' => 'Borrador', 'under_review' => 'En revisión', 'approved' => 'Aprobada', 'rejected' => 'Rechazada', 'retired' => 'Retirada'][$prompt['status']] ?? 'Sin definir' }}</x-ui.badge></div><p class="mt-1 text-sm text-text-secondary">{{ $prompt['purpose'] }}</p></div>@empty<p class="text-sm text-text-secondary">No hay instrucciones registradas.</p>@endforelse</div></x-ui.card>

        <x-ui.card><h2 class="mb-4 font-heading text-lg font-bold">Incidentes</h2>@forelse ($incidents as $incident)<div class="flex flex-wrap items-start justify-between gap-3 border-b border-border py-3 last:border-0"><div><p class="font-medium">{{ $incident['description'] }}</p><p class="text-sm text-text-secondary">{{ $incident['system_name'] }} · detectado {{ \Illuminate\Support\Carbon::parse($incident['discovered_at'])->format('d/m/Y H:i') }}</p></div><div class="flex gap-2"><x-ui.badge :variant="in_array($incident['severity'], ['high', 'critical'], true) ? 'danger' : 'warning'">{{ ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'][$incident['severity']] ?? 'Sin definir' }}</x-ui.badge><x-ui.badge :variant="$incident['status'] === 'resolved' ? 'success' : 'warning'">{{ ['open' => 'Abierto', 'investigating' => 'En investigación', 'mitigated' => 'Mitigado', 'resolved' => 'Resuelto'][$incident['status']] ?? 'Sin definir' }}</x-ui.badge></div></div>@empty<p class="text-sm text-text-secondary">No hay incidentes reportados.</p>@endforelse</x-ui.card>
    </div>
</x-layouts.app>
