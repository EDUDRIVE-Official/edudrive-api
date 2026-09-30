<x-layouts.app title="EDUDRIVE — Mis archivos">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Mis archivos</h1><p class="mt-1 text-sm text-text-secondary">Guardá documentos asociados a tu cuenta. Tamaño máximo por archivo: 20 MB.</p></div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="text-sm text-danger-text">{{ session('error') }}</p>@endif
        <x-ui.card>
            <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                @csrf
                <div class="min-w-0 flex-1"><x-ui.input name="file" label="Seleccionar archivo" type="file" required :error="$errors->first('file')" /><p class="mt-1 text-xs text-text-secondary">El archivo se analizará antes de habilitar su descarga.</p></div>
                <x-ui.button type="submit">Cargar archivo</x-ui.button>
            </form>
        </x-ui.card>
        <x-ui.card>
            <x-ui.table>
                <x-slot:head><tr><th class="px-4 py-2">Archivo</th><th class="px-4 py-2">Tamaño</th><th class="px-4 py-2">Cargado</th><th class="px-4 py-2">Seguridad</th><th class="px-4 py-2">Acciones</th></tr></x-slot:head>
                @forelse ($files as $file)
                    <tr><td class="px-4 py-2"><p class="font-medium">{{ $file['original_filename'] }}</p><p class="text-xs text-text-secondary">{{ $file['mime_type'] }}</p></td><td class="whitespace-nowrap px-4 py-2 text-sm">{{ number_format($file['size_bytes'] / 1024, 1) }} KB</td><td class="whitespace-nowrap px-4 py-2 text-sm">{{ \Illuminate\Support\Carbon::parse($file['uploaded_at'])->format('d/m/Y H:i') }}</td><td class="px-4 py-2"><x-ui.badge :variant="match ($file['scan_status']) { 'clean' => 'success', 'infected' => 'danger', default => 'warning' }">{{ match ($file['scan_status']) { 'pending' => 'Pendiente', 'clean' => 'Limpio', 'infected' => 'Bloqueado', default => $file['scan_status'] } }}</x-ui.badge></td><td class="px-4 py-2"><div class="flex gap-2">@if ($file['scan_status'] === 'clean')<a href="{{ route('files.download', $file['id']) }}" class="inline-flex min-h-[44px] items-center rounded-sm border border-border px-3 text-sm font-medium">Descargar</a>@endif<form method="POST" action="{{ route('files.destroy', $file['id']) }}" onsubmit="return confirm('¿Eliminar este archivo?');">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger" size="sm">Eliminar</x-ui.button></form></div></td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-text-secondary">No has cargado archivos.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.app>
