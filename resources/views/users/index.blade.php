<x-layouts.app title="EDUDRIVE — Usuarios">
    <div class="flex flex-col gap-6">
        <div class="campus-heading flex flex-wrap items-center justify-between gap-4"><div><p class="campus-eyebrow">Gestión de la comunidad</p><h1 class="font-heading text-2xl font-bold">Usuarios</h1><p class="mt-1 text-sm text-text-secondary">Administrá cuentas, accesos y relaciones educativas.</p></div>@if ($canManage)<a href="{{ route('users.create') }}" class="rounded-md bg-primary px-4 py-2 text-sm font-bold text-white">Crear usuario</a>@endif</div>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="font-sans text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        <form method="GET" action="{{ route('users.index') }}" class="grid gap-3 rounded-lg border border-border bg-surface p-4 sm:grid-cols-[1fr_12rem_auto]">
            <x-ui.input name="search" label="Buscar" :value="$search" placeholder="Nombre o correo" />
            <label class="grid gap-1 text-sm font-medium">Estado<select name="status" class="min-h-11 rounded-md border border-border bg-surface px-3"><option value="">Todos</option>@foreach (['active' => 'Activo', 'pending' => 'Pendiente', 'inactive' => 'Inactivo', 'locked' => 'Bloqueado'] as $value => $label)<option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>@endforeach</select></label>
            <div class="flex items-end"><x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button></div>
        </form>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Nombre</th>
                    <th scope="col" class="px-4 py-2">Correo</th>
                    <th scope="col" class="px-4 py-2">Estado</th>
                    <th scope="col" class="px-4 py-2">Roles</th>
                    <th scope="col" class="px-4 py-2">Acciones</th>
                </tr>
            </x-slot:head>
            @forelse ($users as $user)
                <tr>
                    <td class="px-4 py-2">{{ $user['name'] }}</td>
                    <td class="px-4 py-2">{{ $user['email'] }}</td>
                    <td class="px-4 py-2">
                        @php
                            $statusLabels = [
                                'pending' => 'Pendiente',
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'locked' => 'Bloqueado',
                            ];
                            $statusVariants = [
                                'pending' => 'warning',
                                'active' => 'success',
                                'inactive' => 'danger',
                                'locked' => 'danger',
                            ];
                        @endphp
                        <x-ui.badge :variant="$statusVariants[$user['status']] ?? 'info'">
                            {{ $statusLabels[$user['status']] ?? $user['status'] }}
                        </x-ui.badge>
                    </td>
                    <td class="px-4 py-2 text-sm text-text-secondary">{{ collect($user['roles'])->map(fn ($role) => ['super_admin' => 'Superadmin', 'institutional_admin' => 'Admin institucional', 'teacher' => 'Docente', 'student' => 'Estudiante'][$role] ?? $role)->implode(', ') ?: 'Sin rol' }}</td>
                    <td class="px-4 py-2">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('users.show', $user['id']) }}" class="inline-flex min-h-9 items-center rounded-md border border-border px-3 text-xs font-bold hover:bg-background">Ver ficha</a>
                                @if ($canManage)
                                @if ($user['status'] !== 'active')
                                    <form method="POST" action="{{ route('users.activate', $user['id']) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary" size="sm">Activar</x-ui.button>
                                    </form>
                                @endif
                                @if ($user['status'] === 'active')
                                    <form method="POST" action="{{ route('users.deactivate', $user['id']) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="danger" size="sm">Desactivar</x-ui.button>
                                    </form>
                                @endif
                                @endif
                            </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="5">No se encontraron usuarios con esos filtros.</td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($canManageGuardians)
            <section class="grid gap-5 lg:grid-cols-2" aria-labelledby="family-support-title">
                <x-ui.card>
                    <h2 id="family-support-title" class="font-heading text-lg font-bold">Vincular acompañamiento familiar</h2>
                    <p class="mt-1 text-sm leading-6 text-text-secondary">Seleccioná una cuenta adulta y una cuenta menor. La relación quedará registrada y será visible en sus experiencias educativas.</p>
                    <form method="POST" action="{{ route('users.guardians.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="guardian_user_id" class="text-sm font-medium">Persona adulta</label>
                            <select id="guardian_user_id" name="guardian_user_id" required class="mt-1 min-h-[44px] w-full rounded-md border border-border bg-surface px-3 text-text">
                                <option value="">Seleccionar…</option>
                                @foreach ($users as $user)
                                    @if ($user['date_of_birth'] && ! $user['is_minor'])<option value="{{ $user['id'] }}">{{ $user['name'] }}</option>@endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="minor_user_id" class="text-sm font-medium">Persona menor</label>
                            <select id="minor_user_id" name="minor_user_id" required class="mt-1 min-h-[44px] w-full rounded-md border border-border bg-surface px-3 text-text">
                                <option value="">Seleccionar…</option>
                                @foreach ($users as $user)
                                    @if ($user['is_minor'])<option value="{{ $user['id'] }}">{{ $user['name'] }}</option>@endif
                                @endforeach
                            </select>
                        </div>
                        <x-ui.button type="submit">Vincular acompañamiento</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="font-heading text-lg font-bold">Relaciones activas</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($relationships as $relationship)
                            <div class="flex items-center justify-between gap-3 rounded-md border border-border p-3">
                                <p class="text-sm"><strong>{{ $relationship['guardian_name'] }}</strong><br><span class="text-text-secondary">acompaña a {{ $relationship['minor_name'] }}</span></p>
                                <form method="POST" action="{{ route('users.guardians.destroy', $relationship['id']) }}" onsubmit="return confirm('¿Retirar esta relación de acompañamiento?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">Retirar</x-ui.button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-text-secondary">Todavía no hay relaciones activas.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </section>
        @endif
    </div>
</x-layouts.app>
