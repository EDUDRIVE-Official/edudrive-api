<x-layouts.app title="EDUDRIVE — Asignar rol">
    @php
        $roleLabels = [
            'super_admin' => 'Superadministrador',
            'institutional_admin' => 'Administrador institucional',
            'teacher' => 'Docente',
            'student' => 'Estudiante',
        ];
    @endphp
    <div class="mx-auto flex max-w-xl flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Asignar rol</h1>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('roles.assign.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="user_id"
                    label="Identificador del usuario"
                    value="{{ old('user_id') }}"
                    :error="$errors->first('user_id')"
                />

                <div class="flex flex-col gap-1">
                    <label for="role" class="font-sans text-sm font-medium text-text">Rol</label>
                    <select
                        id="role"
                        name="role"
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        <option value="" @selected(old('role') === null)>Selecciona un rol</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                                {{ $roleLabels[$role->value] ?? $role->value }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input
                    name="organization_id"
                    label="Identificador de la organización (opcional)"
                    value="{{ old('organization_id') }}"
                    :error="$errors->first('organization_id')"
                />

                <x-ui.button type="submit" variant="primary">Asignar</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
