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
        <div class="campus-heading"><p class="campus-eyebrow">Accesos y responsabilidades</p><h1 class="font-heading text-2xl font-bold">Asignar rol</h1></div>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('roles.assign.store') }}" class="flex flex-col gap-4">
                @csrf
                <div class="flex flex-col gap-1">
                    <label for="user_id" class="font-sans text-sm font-medium text-text">Usuario</label>
                    <select
                        id="user_id"
                        name="user_id"
                        required
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        <option value="">Selecciona una persona</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') === $user->id)>
                                {{ $user->name }} — {{ $user->email }}{{ $user->status !== 'active' ? ' (inactivo)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

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

                <div class="flex flex-col gap-1">
                    <label for="organization_id" class="font-sans text-sm font-medium text-text">Organización</label>
                    <select
                        id="organization_id"
                        name="organization_id"
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        <option value="">Toda la plataforma (solo roles globales)</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected(old('organization_id') === $organization->id)>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="font-sans text-xs text-muted">Un administrador institucional debe tener una organización; así solo podrá administrar ese centro.</p>
                    @error('organization_id')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.button type="submit" variant="primary">Asignar</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
