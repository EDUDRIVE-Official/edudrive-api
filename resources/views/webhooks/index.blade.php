<x-layouts.app title="EDUDRIVE — Webhooks">
    <div class="space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Webhooks</h1><p class="mt-1 text-sm text-text-secondary">Enviá eventos de EDUDRIVE hacia sistemas externos.</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="text-sm text-danger-text">{{ session('error') }}</p>@endif
        @if (session('webhook_secret'))
            <div class="rounded-md border border-warning bg-warning/10 p-4" role="alert"><p class="font-medium text-warning-text">Secreto de firma — guardalo ahora</p><code class="mt-2 block break-all rounded-sm bg-surface p-3 text-sm select-all">{{ session('webhook_secret') }}</code><p class="mt-2 text-xs text-text-secondary">Se usa para verificar la firma HMAC de cada entrega y no volverá a mostrarse.</p></div>
        @endif
        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Nueva suscripción</h2>
            <form method="POST" action="{{ route('webhooks.store') }}" class="mt-4 space-y-4">
                @csrf
                <x-ui.input name="url" label="URL HTTPS receptora" type="url" value="{{ old('url') }}" maxlength="500" required :error="$errors->first('url')" />
                <fieldset><legend class="mb-1 text-sm font-medium">¿Qué novedades debe recibir el sistema externo?</legend><p class="mb-3 text-xs text-text-secondary">EDUDRIVE enviará un aviso automático cuando ocurra cada evento seleccionado.</p><div class="grid gap-2 sm:grid-cols-2">@foreach ($events as $event)<label class="flex min-h-[44px] items-center gap-2 rounded-sm border border-border px-3"><input type="checkbox" name="events[]" value="{{ $event->value }}" @checked(in_array($event->value, old('events', []), true))><span>{{ $eventLabels[$event->value] }}</span></label>@endforeach</div>@error('events')<p class="mt-1 text-sm text-danger-text">{{ $message }}</p>@enderror</fieldset>
                <x-ui.button type="submit">Registrar webhook</x-ui.button>
            </form>
        </x-ui.card>
        <div class="space-y-4">
            @forelse ($subscriptions as $subscription)
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="break-all font-heading font-bold">{{ $subscription['url'] }}</h2><p class="mt-1 text-sm text-text-secondary">Creado {{ \Illuminate\Support\Carbon::parse($subscription['created_at'])->format('d/m/Y H:i') }}</p></div><x-ui.badge :variant="$subscription['status'] === 'active' ? 'success' : 'warning'">{{ $subscription['status'] === 'active' ? 'Activo' : 'Suspendido' }}</x-ui.badge></div>
                    <div class="mt-3 flex flex-wrap gap-2">@foreach ($subscription['events'] as $event)<x-ui.badge>{{ $eventLabels[$event] ?? 'Evento del sistema' }}</x-ui.badge>@endforeach</div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('webhooks.deliveries', $subscription['id']) }}" class="inline-flex min-h-[44px] items-center rounded-sm border border-border px-3 text-sm font-medium">Ver entregas</a>
                        @if ($subscription['status'] === 'active')<form method="POST" action="{{ route('webhooks.suspend', $subscription['id']) }}">@csrf <x-ui.button type="submit" variant="secondary" size="sm">Suspender</x-ui.button></form>@else<form method="POST" action="{{ route('webhooks.reactivate', $subscription['id']) }}">@csrf <x-ui.button type="submit" variant="secondary" size="sm">Reactivar</x-ui.button></form>@endif
                        <form method="POST" action="{{ route('webhooks.rotate-secret', $subscription['id']) }}" onsubmit="return confirm('El secreto anterior dejará de ser válido. ¿Continuar?');">@csrf <x-ui.button type="submit" variant="secondary" size="sm">Rotar secreto</x-ui.button></form>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card><p class="text-sm text-text-secondary">No hay webhooks registrados.</p></x-ui.card>
            @endforelse
        </div>
    </div>
</x-layouts.app>
