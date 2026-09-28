<x-layouts.app title="EDUDRIVE — Integraciones">
    <div class="space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Integraciones externas</h1><p class="mt-1 text-sm text-text-secondary">Administrá sistemas autorizados para consumir la API de EDUDRIVE.</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="text-sm text-danger-text">{{ session('error') }}</p>@endif
        @if (session('integration_key'))
            <div class="rounded-md border border-warning bg-warning/10 p-4" role="alert">
                <p class="font-medium text-warning-text">Llave de integración — guardala ahora</p>
                <code class="mt-2 block break-all rounded-sm bg-surface p-3 text-sm select-all">{{ session('integration_key') }}</code>
                <p class="mt-2 text-xs text-text-secondary">Por seguridad, EDUDRIVE no almacena ni vuelve a mostrar esta llave en texto plano.</p>
            </div>
        @endif

        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Registrar integración</h2>
            <form method="POST" action="{{ route('integrations.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="name" label="Nombre del sistema" value="{{ old('name') }}" maxlength="150" required :error="$errors->first('name')" />
                    <x-ui.input name="expires_at" label="Vencimiento (opcional)" type="datetime-local" value="{{ old('expires_at') }}" :error="$errors->first('expires_at')" />
                </div>
                <fieldset>
                    <legend class="mb-2 text-sm font-medium">Alcances permitidos</legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($scopes as $scope => $label)
                            <label class="flex min-h-[44px] items-center gap-2 rounded-sm border border-border px-3"><input type="checkbox" name="scopes[]" value="{{ $scope }}" @checked(in_array($scope, old('scopes', []), true))><span class="text-sm">{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('scopes')<p class="mt-1 text-sm text-danger-text">{{ $message }}</p>@enderror
                </fieldset>
                <x-ui.button type="submit">Registrar y generar llave</x-ui.button>
            </form>
        </x-ui.card>

        <div class="space-y-4">
            @forelse ($consumers as $consumer)
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-heading text-lg font-bold">{{ $consumer['name'] }}</h2><p class="mt-1 text-sm text-text-secondary">Creada {{ \Illuminate\Support\Carbon::parse($consumer['created_at'])->format('d/m/Y H:i') }} · vence {{ $consumer['expires_at'] ? \Illuminate\Support\Carbon::parse($consumer['expires_at'])->format('d/m/Y H:i') : 'sin vencimiento' }}</p></div>
                        <x-ui.badge :variant="match ($consumer['status']) { 'active' => 'success', 'suspended' => 'warning', default => 'danger' }">{{ match ($consumer['status']) { 'active' => 'Activa', 'suspended' => 'Suspendida', 'revoked' => 'Revocada', default => $consumer['status'] } }}</x-ui.badge>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">@foreach ($consumer['scopes'] as $scope)<x-ui.badge>{{ $scopes[$scope] ?? $scope }}</x-ui.badge>@endforeach</div>
                    @if ($consumer['status'] !== 'revoked')
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($consumer['status'] === 'active')
                                <form method="POST" action="{{ route('integrations.suspend', $consumer['id']) }}" class="flex gap-2">@csrf <input name="reason" aria-label="Motivo de suspensión" placeholder="Motivo opcional" maxlength="255" class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-sm"><x-ui.button type="submit" variant="secondary" size="sm">Suspender</x-ui.button></form>
                            @else
                                <form method="POST" action="{{ route('integrations.reactivate', $consumer['id']) }}">@csrf <x-ui.button type="submit" variant="secondary" size="sm">Reactivar</x-ui.button></form>
                            @endif
                            <form method="POST" action="{{ route('integrations.rotate-key', $consumer['id']) }}" onsubmit="return confirm('La llave anterior dejará de funcionar. ¿Continuar?');">@csrf <x-ui.button type="submit" variant="secondary" size="sm">Rotar llave</x-ui.button></form>
                            <form method="POST" action="{{ route('integrations.revoke', $consumer['id']) }}" onsubmit="return confirm('Esta acción es definitiva. ¿Revocar integración?');">@csrf <input type="hidden" name="reason" value="Revocada desde el panel administrativo"><x-ui.button type="submit" variant="danger" size="sm">Revocar</x-ui.button></form>
                        </div>
                    @endif
                </x-ui.card>
            @empty
                <x-ui.card><p class="text-sm text-text-secondary">No hay integraciones externas registradas.</p></x-ui.card>
            @endforelse
        </div>
    </div>
</x-layouts.app>
