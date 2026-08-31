<x-layouts.app title="EDUDRIVE — Usuarios">
    <div class="flex flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Usuarios</h1>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Nombre</th>
                    <th scope="col" class="px-4 py-2">Correo</th>
                    <th scope="col" class="px-4 py-2">Estado</th>
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
                    <td class="px-4 py-2">
                        @if ($canManage)
                            <div class="flex gap-2">
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
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="4">Todavía no hay usuarios registrados.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
