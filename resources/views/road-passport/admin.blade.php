<x-layouts.app title="EDUDRIVE — Pasaportes viales">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Pasaportes viales</h1>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        @if (session('error'))
            <p class="font-sans text-sm text-danger-text">{{ session('error') }}</p>
        @endif

        <x-ui.card>
            <form method="GET" action="{{ route('road-passport.admin.search') }}" class="flex items-end gap-3">
                <div class="flex flex-1 flex-col gap-1">
                    <label for="user_id" class="font-sans text-sm font-medium text-text">Estudiante</label>
                    <select id="user_id" name="user_id" required class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text">
                        <option value="">Selecciona un estudiante</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($searchedUserId === $user->id)>{{ $user->name }} — {{ $user->email }}{{ $user->status !== 'active' ? ' (inactivo)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.button type="submit" variant="primary">Buscar</x-ui.button>
            </form>
        </x-ui.card>

        @if ($searchedUserId && $notFound)
            <x-ui.card>
                <p class="font-sans text-sm text-text-secondary">
                    Este usuario no tiene un pasaporte vial emitido.
                </p>
                @if ($canManage)
                    <form method="POST" action="{{ route('road-passport.admin.issue') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $searchedUserId }}">
                        <x-ui.button type="submit" variant="primary">Emitir pasaporte vial</x-ui.button>
                    </form>
                @endif
            </x-ui.card>
        @endif

        @if ($passport)
            @php
                $statusLabels = ['active' => 'Activo', 'suspended' => 'Suspendido', 'revoked' => 'Revocado'];
                $statusVariants = ['active' => 'success', 'suspended' => 'warning', 'revoked' => 'danger'];
            @endphp

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <span class="font-sans text-sm text-text-secondary">Estudiante: {{ $selectedUserName ?? 'Usuario no disponible' }}</span>
                        <span class="font-sans text-sm text-text-secondary">Nivel {{ $passport['level'] }}</span>
                        <span class="font-sans text-sm text-text-secondary">Puntaje de confianza: {{ $passport['trust_score'] }}</span>
                    </div>
                    <x-ui.badge :variant="$statusVariants[$passport['status']] ?? 'info'">
                        {{ $statusLabels[$passport['status']] ?? $passport['status'] }}
                    </x-ui.badge>
                </div>

                @if ($canManage)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($passport['status'] === 'active')
                            <form method="POST" action="{{ route('road-passport.admin.level', $passport['id']) }}" class="flex items-end gap-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="user_id" value="{{ $passport['user_id'] }}">
                                <x-ui.input name="level" type="number" label="Nuevo nivel" />
                                <x-ui.button type="submit" variant="secondary" size="sm">Cambiar nivel</x-ui.button>
                            </form>

                            <form method="POST" action="{{ route('road-passport.admin.suspend', $passport['id']) }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $passport['user_id'] }}">
                                <x-ui.button type="submit" variant="secondary" size="sm">Suspender</x-ui.button>
                            </form>
                        @endif

                        @if ($passport['status'] === 'suspended')
                            <form method="POST" action="{{ route('road-passport.admin.reactivate', $passport['id']) }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $passport['user_id'] }}">
                                <x-ui.button type="submit" variant="secondary" size="sm">Reactivar</x-ui.button>
                            </form>
                        @endif

                        @if ($passport['status'] !== 'revoked')
                            <form
                                method="POST"
                                action="{{ route('road-passport.admin.revoke', $passport['id']) }}"
                                onsubmit="return confirm('¿Seguro que querés revocar este pasaporte? No se puede deshacer.');"
                            >
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $passport['user_id'] }}">
                                <x-ui.button type="submit" variant="danger" size="sm">Revocar</x-ui.button>
                            </form>
                        @endif
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h2 class="font-heading text-lg font-bold">Historial</h2>
                @if (count($passport['history']) > 0)
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th scope="col" class="px-4 py-2">Cambio</th>
                                <th scope="col" class="px-4 py-2">De</th>
                                <th scope="col" class="px-4 py-2">A</th>
                                <th scope="col" class="px-4 py-2">Fecha</th>
                                <th scope="col" class="px-4 py-2">Motivo</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($passport['history'] as $entry)
                            <tr>
                                <td class="px-4 py-2">{{ $entry['type'] === 'level_changed' ? 'Cambio de nivel' : 'Cambio de estado' }}</td>
                                <td class="px-4 py-2">{{ $entry['from'] }}</td>
                                <td class="px-4 py-2">{{ $entry['to'] }}</td>
                                <td class="px-4 py-2">{{ $entry['occurred_at'] }}</td>
                                <td class="px-4 py-2">{{ $entry['reason'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @else
                    <p class="font-sans text-sm text-text-secondary">Todavía no hay cambios registrados.</p>
                @endif
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
