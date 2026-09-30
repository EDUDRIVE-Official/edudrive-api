<x-layouts.app title="EDUDRIVE — Consentimientos de menores">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <div class="campus-heading"><h1 class="font-heading text-2xl font-bold">Consentimientos de menores</h1></div>
        <x-ui.card>
            <form method="GET" action="{{ route('legal.admin.minors') }}" class="flex items-end gap-3">
                <div class="flex flex-1 flex-col gap-1">
                    <label for="organization_id" class="text-sm font-medium text-text">Organización</label>
                    <select id="organization_id" name="organization_id" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3 text-base text-text">
                        <option value="">Selecciona una organización</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected($organizationId === $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.button type="submit">Consultar</x-ui.button>
            </form>
        </x-ui.card>
        @if ($organizationId)
            <x-ui.card>
                <x-ui.table>
                    <x-slot:head><tr><th class="px-4 py-2">Estudiante</th><th class="px-4 py-2">Consentimientos</th></tr></x-slot:head>
                    @forelse ($minors as $minor)
                        <tr><td class="px-4 py-2"><strong>{{ $minor['name'] }}</strong></td><td class="px-4 py-2">{{ count($minor['consents']) }} registrados</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-4 text-text-secondary">No se encontraron menores en esta organización.</td></tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
