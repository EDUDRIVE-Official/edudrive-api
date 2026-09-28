<x-layouts.app title="EDUDRIVE — Políticas legales">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Políticas legales</h1></div>
        @if (session('status'))
            <p class="text-sm text-success-text">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="text-sm text-danger-text">{{ session('error') }}</p>
        @endif
        <x-ui.card>
            <form method="POST" action="{{ route('legal.admin.policies.publish') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div class="flex flex-col gap-1">
                    <label for="key" class="text-sm font-medium text-text">Política</label>
                    <select id="key" name="key" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-base text-text">
                        <option value="">Selecciona una política</option>
                        @foreach ($policyLabels as $key => $label)<option value="{{ $key }}" @selected(old('key') === $key)>{{ $label }}</option>@endforeach
                    </select>
                    @error('key')<p class="text-sm text-danger-text">{{ $message }}</p>@enderror
                </div>
                <x-ui.input name="effective_at" type="datetime-local" label="Vigencia (opcional)" value="{{ old('effective_at') }}" />
                <div><x-ui.button type="submit">Publicar nueva versión</x-ui.button></div>
            </form>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-3 font-heading text-lg font-bold">Versiones vigentes</h2>
            <x-ui.table>
                <x-slot:head><tr><th class="px-4 py-2">Política</th><th class="px-4 py-2">Versión</th><th class="px-4 py-2">Vigencia</th></tr></x-slot:head>
                @foreach ($policies as $policy)
                    <tr><td class="px-4 py-2">{{ $policyLabels[$policy['key']] ?? str($policy['key'])->replace('_', ' ')->title() }}</td><td class="px-4 py-2">{{ $policy['version'] }}</td><td class="px-4 py-2">{{ $policy['effective_at'] }}</td></tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.app>
