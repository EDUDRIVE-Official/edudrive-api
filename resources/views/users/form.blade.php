<x-layouts.app :title="$managedUser ? 'EDUDRIVE — Editar usuario' : 'EDUDRIVE — Crear usuario'">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ $managedUser ? route('users.show', $managedUser['id']) : route('users.index') }}" class="text-sm font-medium text-primary hover:underline">← Volver</a>
            <h1 class="mt-3 font-heading text-2xl font-bold">{{ $managedUser ? 'Editar usuario' : 'Crear usuario' }}</h1>
            <p class="mt-1 text-sm text-text-secondary">Los datos personales deben ser los mínimos necesarios para la experiencia educativa.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm text-danger-text" role="alert">
                <p class="font-bold">Revisá la información:</p>
                <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ $managedUser ? route('users.update', $managedUser['id']) : route('users.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                @if ($managedUser) @method('PUT') @endif
                <div class="sm:col-span-2"><x-ui.input name="name" label="Nombre completo" :value="old('name', $managedUser['name'] ?? '')" :error="$errors->first('name')" required /></div>
                <div class="sm:col-span-2"><x-ui.input name="email" label="Correo electrónico" type="email" :value="old('email', $managedUser['email'] ?? '')" :error="$errors->first('email')" required /></div>
                <x-ui.input name="date_of_birth" label="Fecha de nacimiento" type="date" :value="old('date_of_birth', $managedUser['date_of_birth'] ?? '')" :error="$errors->first('date_of_birth')" />

                @if (! $managedUser)
                    <label class="grid gap-1 text-sm font-medium">Rol inicial
                        <select name="role" required class="min-h-11 rounded-md border border-border bg-surface px-3">
                            @foreach ($assignableRoles as $role)<option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ ['super_admin' => 'Superadministrador', 'institutional_admin' => 'Administrador institucional', 'teacher' => 'Docente', 'student' => 'Estudiante'][$role->value] }}</option>@endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm font-medium">Organización
                        <select name="organization_id" class="min-h-11 rounded-md border border-border bg-surface px-3">
                            <option value="">Sin organización / alcance general</option>
                            @foreach ($organizations as $id => $name)<option value="{{ $id }}" @selected(old('organization_id') === $id)>Solo {{ $name }}</option>@endforeach
                        </select>
                        <span class="text-xs font-normal text-text-secondary">Es obligatoria para administradores institucionales.</span>
                    </label>
                    <x-ui.input name="password" label="Contraseña inicial" type="password" :error="$errors->first('password')" required />
                    <x-ui.input name="password_confirmation" label="Confirmar contraseña" type="password" required />
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="activate_now" value="1" @checked(old('activate_now'))> Activar la cuenta inmediatamente</label>
                @endif

                <div class="sm:col-span-2"><x-ui.button type="submit">{{ $managedUser ? 'Guardar cambios' : 'Crear usuario' }}</x-ui.button></div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
