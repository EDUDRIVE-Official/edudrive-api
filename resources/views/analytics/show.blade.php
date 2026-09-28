<x-layouts.app title="EDUDRIVE — Resultado analítico">
    <div class="mx-auto max-w-3xl space-y-6">
        <div><a href="{{ route('analytics.index') }}" class="text-sm text-primary">← Volver a analítica</a><h1 class="mt-2 font-heading text-2xl font-bold">Resultado analítico</h1><p class="mt-1 text-sm text-text-secondary">Solicitado {{ \Illuminate\Support\Carbon::parse($job['created_at'])->format('d/m/Y H:i') }}</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm text-text-secondary">Tipo</p><h2 class="font-heading text-lg font-bold">{{ match ($job['type']) { 'analytics.enrollments_summary' => 'Resumen de matrículas', 'analytics.certifications_summary' => 'Resumen de certificados', 'analytics.users_summary' => 'Resumen de usuarios', default => str($job['type'])->replace(['analytics.', '_'], ['', ' '])->title() } }}</h2></div><x-ui.badge :variant="match ($job['status']) { 'completed' => 'success', 'failed' => 'danger', default => 'warning' }">{{ match ($job['status']) { 'pending' => 'Pendiente', 'processing' => 'Procesando', 'completed' => 'Completado', 'failed' => 'Falló', default => $job['status'] } }}</x-ui.badge></div>
            @if (in_array($job['status'], ['pending', 'processing'], true))<p class="mt-4 text-sm text-text-secondary">El reporte aún se está procesando. Actualizá esta página en unos segundos.</p>@endif
            @if ($job['failure_reason'])<p class="mt-4 text-sm text-danger-text">{{ $job['failure_reason'] }}</p>@endif
        </x-ui.card>
        @if ($job['status'] === 'completed' && $job['result'])
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card><p class="text-sm text-text-secondary">Total</p><p class="mt-1 font-heading text-4xl font-bold">{{ $job['result']['total'] ?? 0 }}</p></x-ui.card>
                <x-ui.card><h2 class="mb-3 font-heading font-bold">Por estado</h2>@forelse (($job['result']['by_status'] ?? []) as $status => $count)<div class="flex justify-between border-b border-border py-2 last:border-0"><span>{{ str($status)->replace('_', ' ')->title() }}</span><strong>{{ $count }}</strong></div>@empty<p class="text-sm text-text-secondary">Sin datos.</p>@endforelse</x-ui.card>
            </div>
        @endif
    </div>
</x-layouts.app>
