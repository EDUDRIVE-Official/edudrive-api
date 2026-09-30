<x-layouts.app title="EDUDRIVE — Mis dispositivos">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Mis dispositivos</h1><p class="mt-1 text-sm text-text-secondary">Revisá los teléfonos o tabletas vinculados a tu cuenta y revocá los que ya no utilizás.</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="text-sm text-danger-text">{{ session('error') }}</p>@endif
        <div class="grid gap-4 sm:grid-cols-2">
            @forelse ($devices as $device)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3"><div><h2 class="font-heading text-lg font-bold">{{ $device['platform'] === 'ios' ? 'Dispositivo Apple' : 'Dispositivo Android' }} {{ $loop->iteration }}</h2><p class="mt-1 text-sm text-text-secondary">Vinculado con tu cuenta de EDUDRIVE</p></div><x-ui.badge :variant="$device['has_push_token'] ? 'success' : 'warning'">{{ $device['has_push_token'] ? 'Avisos activos' : 'Avisos desactivados' }}</x-ui.badge></div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-text-secondary">Versión de app</dt><dd class="font-medium">{{ $device['app_version'] }}</dd></div><div><dt class="text-text-secondary">Último acceso</dt><dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($device['last_seen_at'])->format('d/m/Y H:i') }}</dd></div></dl>
                    <form method="POST" action="{{ route('mobile.devices.destroy', $device['device_id']) }}" class="mt-4" onsubmit="return confirm('¿Desvincular este dispositivo? Dejará de recibir notificaciones.');">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger" size="sm">Desvincular</x-ui.button></form>
                </x-ui.card>
            @empty
                <x-ui.card class="sm:col-span-2"><p class="text-sm text-text-secondary">No hay dispositivos móviles vinculados. Aparecerán aquí cuando iniciés sesión desde la futura aplicación móvil de EDUDRIVE.</p></x-ui.card>
            @endforelse
        </div>
    </div>
</x-layouts.app>
