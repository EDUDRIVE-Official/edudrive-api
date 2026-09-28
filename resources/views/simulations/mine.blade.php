<x-layouts.app title="EDUDRIVE — Mis simulaciones">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="campus-heading">
            <h1 class="font-heading text-2xl font-bold">Mis simulaciones</h1>
            <p class="mt-1 text-sm text-text-secondary">Historial de prácticas realizadas o programadas en SIMUDRIVE.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.card><p class="text-sm text-text-secondary">Total</p><p class="mt-1 font-heading text-3xl font-bold">{{ count($sessions) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm text-text-secondary">Completadas</p><p class="mt-1 font-heading text-3xl font-bold">{{ collect($sessions)->where('status', 'completed')->count() }}</p></x-ui.card>
            <x-ui.card><p class="text-sm text-text-secondary">Programadas</p><p class="mt-1 font-heading text-3xl font-bold">{{ collect($sessions)->where('status', 'scheduled')->count() }}</p></x-ui.card>
        </div>

        @forelse ($sessions as $session)
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-heading text-lg font-bold">{{ str($session['scenario'])->replace(['-', '_'], ' ')->title() }}</h2>
                        <p class="mt-1 text-sm text-text-secondary">{{ str($session['vehicle_type'])->replace('_', ' ')->title() }} · {{ \Illuminate\Support\Carbon::parse($session['scheduled_at'])->format('d/m/Y H:i') }}</p>
                    </div>
                    <x-ui.badge :variant="match ($session['status']) { 'completed' => 'success', 'cancelled' => 'danger', 'in_progress' => 'warning', default => 'info' }">
                        {{ match ($session['status']) { 'scheduled' => 'Programada', 'in_progress' => 'En curso', 'completed' => 'Completada', 'cancelled' => 'Cancelada', default => $session['status'] } }}
                    </x-ui.badge>
                </div>
                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><span class="text-text-secondary">Duración prevista</span><p class="font-medium">{{ $session['planned_duration_minutes'] }} minutos</p></div>
                    <div><span class="text-text-secondary">Duración real</span><p class="font-medium">{{ $session['actual_duration_minutes'] !== null ? $session['actual_duration_minutes'].' minutos' : '—' }}</p></div>
                    <div><span class="text-text-secondary">Cambios de estado</span><p class="font-medium">{{ count($session['history']) }}</p></div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card><p class="text-sm text-text-secondary">Todavía no tenés sesiones de simulación. Este panel mostrará los resultados cuando SIMUDRIVE registre una práctica.</p></x-ui.card>
        @endforelse
    </div>
</x-layouts.app>
