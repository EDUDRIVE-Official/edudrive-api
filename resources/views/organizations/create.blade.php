@php
    $typeLabels = [
        'educational_center' => 'Centro educativo',
        'driving_school' => 'Escuela de manejo',
        'company' => 'Empresa',
        'public_institution' => 'Institución pública',
        'other' => 'Otro',
    ];
@endphp
<x-layouts.app title="EDUDRIVE — Nueva organización">
    <div class="mx-auto flex max-w-sm flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Nueva organización</h1>

        <x-ui.card>
            <form method="POST" action="{{ route('organizations.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="name"
                    label="Nombre"
                    value="{{ old('name') }}"
                    :error="$errors->first('name')"
                />

                <div class="flex flex-col gap-1">
                    <label for="type" class="font-sans text-sm font-medium text-text">Tipo de organización</label>
                    <select
                        id="type"
                        name="type"
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                                {{ $typeLabels[$type->value] ?? $type->value }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.button type="submit" variant="primary">Crear</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
