<x-layouts.app title="EDUDRIVE — Configuración del sistema">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="campus-heading flex flex-wrap items-start justify-between gap-4">
            <div><h1 class="font-heading text-2xl font-bold">Configuración del sistema</h1><p class="mt-1 text-sm text-text-secondary">Parámetros operativos registrados en EDUDRIVE.</p></div>
            <a href="{{ route('admin.system.summary') }}" class="inline-flex min-h-[44px] items-center rounded-sm border border-border px-4 text-sm font-medium">Volver al resumen</a>
        </div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        @forelse ($settings as $setting)
            @php
                $information = $settingInformation[$setting['key']] ?? [
                    'label' => str($setting['key'])->replace('_', ' ')->title(),
                    'description' => 'Parámetro operativo de EDUDRIVE.',
                    'type' => 'text',
                ];
            @endphp
            <x-ui.card>
                <form method="POST" action="{{ route('admin.system.settings.update', $setting['key']) }}" class="flex flex-wrap items-end gap-4">
                    @csrf
                    @method('PUT')
                    <div class="min-w-0 flex-1">
                        @if ($information['type'] === 'boolean')
                            <label for="value-{{ $setting['key'] }}" class="text-sm font-medium text-text">{{ $information['label'] }}</label>
                            <select id="value-{{ $setting['key'] }}" name="value" required class="mt-1 min-h-[44px] w-full rounded-sm border border-border bg-surface px-3 text-base text-text">
                                <option value="true" @selected($setting['value'] === 'true')>Activado</option>
                                <option value="false" @selected($setting['value'] === 'false')>Desactivado</option>
                            </select>
                        @else
                            <x-ui.input name="value" :label="$information['label']" :value="$setting['value']" required :error="$errors->first('value')" />
                        @endif
                        <p class="mt-1 text-xs text-text-secondary">{{ $information['description'] }} Última actualización: {{ \Illuminate\Support\Carbon::parse($setting['changed_at'])->format('d/m/Y H:i') }}.</p>
                    </div>
                    <x-ui.button type="submit">Guardar</x-ui.button>
                </form>
            </x-ui.card>
        @empty
            <x-ui.card><p class="text-sm text-text-secondary">Todavía no se han registrado parámetros de configuración.</p></x-ui.card>
        @endforelse
    </div>
</x-layouts.app>
