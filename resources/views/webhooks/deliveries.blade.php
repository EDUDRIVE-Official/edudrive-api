<x-layouts.app title="EDUDRIVE — Entregas de webhook">
    <div class="space-y-6">
        <div><a href="{{ route('webhooks.index') }}" class="text-sm text-primary">← Volver a webhooks</a><h1 class="mt-2 font-heading text-2xl font-bold">Entregas</h1><p class="mt-1 break-all text-sm text-text-secondary">{{ $subscription['url'] }}</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="text-sm text-danger-text">{{ session('error') }}</p>@endif
        <form method="GET" class="flex flex-wrap items-end gap-3"><label class="flex flex-col gap-1 text-sm font-medium">Estado<select name="status" class="min-h-[44px] rounded-sm border border-border bg-surface px-3"><option value="">Todos</option>@foreach ($statuses as $status)<option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select></label><x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button></form>
        <x-ui.card>
            <x-ui.table>
                <x-slot:head><tr><th class="px-4 py-2">Fecha</th><th class="px-4 py-2">Evento</th><th class="px-4 py-2">Estado</th><th class="px-4 py-2">Intentos</th><th class="px-4 py-2">Respuesta</th><th class="px-4 py-2">Acción</th></tr></x-slot:head>
                @forelse ($deliveries as $delivery)
                    <tr><td class="whitespace-nowrap px-4 py-2 text-sm">{{ \Illuminate\Support\Carbon::parse($delivery['created_at'])->format('d/m/Y H:i') }}</td><td class="px-4 py-2 text-sm">{{ $eventLabels[$delivery['event_name']] ?? 'Evento del sistema' }}</td><td class="px-4 py-2"><x-ui.badge :variant="match ($delivery['status']) { 'delivered' => 'success', 'failed', 'dead_lettered' => 'danger', default => 'warning' }">{{ ['pending' => 'Pendiente', 'processing' => 'Procesando', 'delivered' => 'Entregada', 'failed' => 'Falló', 'dead_lettered' => 'Sin más reintentos'][$delivery['status']] ?? 'Sin definir' }}</x-ui.badge></td><td class="px-4 py-2">{{ $delivery['attempts'] }}</td><td class="px-4 py-2 text-sm">{{ $delivery['last_response_status'] ?? '—' }}</td><td class="px-4 py-2">@if (in_array($delivery['status'], ['failed', 'dead_lettered'], true))<form method="POST" action="{{ route('webhooks.retry', $delivery['id']) }}">@csrf <x-ui.button type="submit" variant="secondary" size="sm">Reintentar</x-ui.button></form>@else — @endif</td></tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-text-secondary">No hay entregas con este filtro.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.app>
