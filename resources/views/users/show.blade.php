<x-layouts.app :title="'EDUDRIVE — '.$managedUser['name']">
    @php
        $roleLabels = ['super_admin' => 'Superadministrador', 'institutional_admin' => 'Administrador institucional', 'teacher' => 'Docente', 'student' => 'Estudiante'];
        $actionLabels = ['identity.admin_user_created' => 'Usuario creado', 'identity.admin_user_updated' => 'Datos actualizados', 'identity.account_activated' => 'Cuenta activada', 'identity.account_deactivated' => 'Cuenta desactivada', 'identity.admin_temporary_password_issued' => 'Contraseña temporal generada', 'identity.student_learning_reset' => 'Expediente educativo reiniciado', 'authorization.role_assigned' => 'Rol asignado', 'authorization.role_revoked' => 'Rol retirado'];
        $resetCategoryLabels = [
            'academic_enrollments' => 'Matrículas',
            'academic_enrollment_lesson_completions' => 'Lecciones completadas',
            'academic_exam_attempts' => 'Evaluaciones',
            'learning_events' => 'Eventos de aprendizaje',
            'road_passports' => 'Pasaporte vial anterior',
            'road_passport_history_entries' => 'Historial del pasaporte',
            'road_passport_evidence' => 'Evidencias del pasaporte',
            'certificates' => 'Certificados',
            'user_achievements' => 'Logros',
            'user_badges' => 'Insignias',
            'experience_entries' => 'Experiencia',
            'challenge_participations' => 'Retos',
            'simulation_sessions' => 'Prácticas de simulación',
        ];
    @endphp
    <div class="space-y-6">
        <div>
            <a href="{{ route('users.index') }}" class="text-sm font-medium text-primary hover:underline">← Volver a usuarios</a>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div><h1 class="font-heading text-2xl font-bold">{{ $managedUser['name'] }}</h1><p class="text-sm text-text-secondary">{{ $managedUser['email'] }}</p></div>
                @if ($canManage)<a href="{{ route('users.edit', $managedUser['id']) }}" class="rounded-md bg-primary px-4 py-2 text-sm font-bold text-white">Editar datos</a>@endif
            </div>
        </div>

        @if (session('status'))<div class="rounded-lg border border-success/30 bg-success/10 p-4 text-sm font-medium text-success-text">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-sm text-danger-text"><p class="font-bold">Revisá la información:</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if (session('temporary_password'))
            <div class="rounded-lg border-2 border-warning bg-warning/10 p-4" role="status"><p class="font-bold">Contraseña temporal — se muestra una sola vez</p><code class="mt-2 block select-all rounded bg-background p-3 text-lg">{{ session('temporary_password') }}</code><p class="mt-2 text-xs text-text-secondary">Compartila por un canal seguro y pedí que sea cambiada al ingresar.</p></div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card><p class="text-xs text-text-secondary">Estado</p><p class="mt-1 font-bold">{{ ['pending' => 'Pendiente', 'active' => 'Activo', 'inactive' => 'Inactivo', 'locked' => 'Bloqueado'][$managedUser['status']] ?? $managedUser['status'] }}</p></x-ui.card>
            <x-ui.card><p class="text-xs text-text-secondary">Matrículas</p><p class="mt-1 text-2xl font-bold text-primary">{{ $enrollmentCount }}</p></x-ui.card>
            <x-ui.card><p class="text-xs text-text-secondary">Pasaporte Vial</p><p class="mt-1 font-bold">{{ $hasPassport ? 'Emitido' : 'Sin emitir' }}</p></x-ui.card>
            <x-ui.card><p class="text-xs text-text-secondary">Sesiones activas</p><p class="mt-1 text-2xl font-bold">{{ $activeSessionCount }}</p></x-ui.card>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="font-heading text-lg font-bold">Datos de la cuenta</h2>
                <dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-text-secondary">Fecha de nacimiento</dt><dd class="font-medium">{{ $managedUser['date_of_birth'] ? \Illuminate\Support\Carbon::parse($managedUser['date_of_birth'])->format('d/m/Y') : 'No registrada' }}</dd></div><div><dt class="text-text-secondary">Correo verificado</dt><dd class="font-medium">{{ $managedUser['email_verified_at'] ? 'Sí' : 'No' }}</dd></div><div><dt class="text-text-secondary">Creado</dt><dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($managedUser['created_at'])->format('d/m/Y H:i') }}</dd></div></dl>
            </x-ui.card>
            <x-ui.card>
                <h2 class="font-heading text-lg font-bold">Roles y organizaciones</h2>
                <div class="mt-4 space-y-2">@forelse ($roles as $role)<div class="flex items-center justify-between gap-3 rounded-md border border-border p-3"><p class="text-sm"><strong>{{ $roleLabels[$role['role']] ?? $role['role'] }}</strong><br><span class="text-text-secondary">{{ $role['organization_name'] }}</span></p>@if ($canManageRoles && ! $isSelf)<form method="POST" action="{{ route('users.roles.destroy', [$managedUser['id'], $role['id']]) }}">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger" size="sm">Retirar</x-ui.button></form>@endif</div>@empty<p class="text-sm text-text-secondary">Sin roles activos.</p>@endforelse</div>
                @if ($canManageRoles)
                    <form method="POST" action="{{ route('roles.assign.store') }}" class="mt-4 grid gap-3 border-t border-border pt-4">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $managedUser['id'] }}">
                        <input type="hidden" name="return_to_user" value="1">
                        <label class="grid gap-1 text-sm font-medium">Rol
                            <select name="role" required class="min-h-11 rounded-md border border-border bg-surface px-3">@foreach ($assignableRoles as $role)<option value="{{ $role->value }}">{{ $roleLabels[$role->value] }}</option>@endforeach</select>
                        </label>
                        <label class="grid gap-1 text-sm font-medium">Alcance del rol
                            <select name="organization_id" class="min-h-11 rounded-md border border-border bg-surface px-3"><option value="">Toda la plataforma (solo roles globales)</option>@foreach ($organizations as $id => $name)<option value="{{ $id }}">Solo {{ $name }}</option>@endforeach</select>
                        </label>
                        <p class="text-xs text-text-secondary">Para un administrador institucional, seleccioná obligatoriamente el centro que podrá administrar.</p>
                        <x-ui.button type="submit">Asignar rol</x-ui.button>
                    </form>
                @endif
            </x-ui.card>
        </div>

        @if ($canManage)
            <x-ui.card><h2 class="font-heading text-lg font-bold">Administrar acceso</h2><div class="mt-4 flex flex-wrap gap-3">@if ($managedUser['status'] !== 'active')<form method="POST" action="{{ route('users.activate', $managedUser['id']) }}">@csrf<x-ui.button>Activar o desbloquear</x-ui.button></form>@elseif (! $isSelf)<form method="POST" action="{{ route('users.deactivate', $managedUser['id']) }}">@csrf<x-ui.button variant="secondary">Desactivar</x-ui.button></form>@endif<form method="POST" action="{{ route('users.temporary-password', $managedUser['id']) }}" onsubmit="return confirm('¿Generar una contraseña temporal y cerrar todas las sesiones?')">@csrf<x-ui.button variant="secondary">Generar contraseña temporal</x-ui.button></form></div></x-ui.card>
        @endif

        <x-ui.card><h2 class="font-heading text-lg font-bold">Historial administrativo</h2><div class="mt-4 space-y-2">@forelse ($auditEntries as $entry)<div class="flex justify-between gap-4 border-b border-border py-2 text-sm"><span>{{ $actionLabels[$entry->action] ?? $entry->action }}</span><time class="shrink-0 text-text-secondary">{{ $entry->occurred_at->format('d/m/Y H:i') }}</time></div>@empty<p class="text-sm text-text-secondary">Sin acciones administrativas registradas.</p>@endforelse</div></x-ui.card>

        @if ($learningResets->isNotEmpty())
            <x-ui.card><h2 class="font-heading text-lg font-bold">Reinicios del aprendizaje</h2><p class="mt-1 text-sm text-text-secondary">Cada reinicio conserva una copia cifrada del expediente anterior para auditoría.</p><div class="mt-4 space-y-3">@foreach ($learningResets as $reset)<div class="rounded-md border border-border p-3 text-sm"><div class="flex flex-wrap justify-between gap-2"><strong>{{ $reset->reset_at->format('d/m/Y H:i') }}</strong><span class="text-text-secondary">Por {{ $resetActors[$reset->performed_by_user_id] ?? 'Administrador anterior' }}</span></div><p class="mt-2">{{ $reset->reason }}</p>@php($visibleSummary = collect($reset->summary ?? [])->filter(fn ($count, $table) => $count > 0 && isset($resetCategoryLabels[$table])))@if ($visibleSummary->isNotEmpty())<div class="mt-3 flex flex-wrap gap-2">@foreach ($visibleSummary as $table => $count)<span class="rounded-full bg-background px-2.5 py-1 text-xs font-medium">{{ $resetCategoryLabels[$table] }}: {{ $count }}</span>@endforeach</div>@endif<p class="mt-3 text-xs text-text-secondary">{{ array_sum($reset->summary ?? []) }} registros educativos archivados · Referencia {{ $reset->id }}</p></div>@endforeach</div></x-ui.card>
        @endif

        @if ($canResetLearning)
            <section class="rounded-lg border border-warning/50 bg-warning/5 p-5"><h2 class="font-heading text-lg font-bold">Reiniciar aprendizaje del estudiante</h2><p class="mt-2 text-sm text-text-secondary">Guarda una copia cifrada del expediente actual y elimina matrículas, avances, evaluaciones, certificados, evidencias del pasaporte, logros y simulaciones. La cuenta, los roles y la organización se conservan.</p><form method="POST" action="{{ route('users.learning.reset', $managedUser['id']) }}" class="mt-4 grid max-w-2xl gap-3" onsubmit="return confirm('¿Confirmás el reinicio completo del aprendizaje? El expediente actual dejará de estar activo.')">@csrf<label class="grid gap-1 text-sm font-medium">Motivo del reinicio<textarea name="reason" required minlength="10" maxlength="500" rows="3" class="rounded-md border border-border bg-surface px-3 py-2">{{ old('reason') }}</textarea></label><label class="grid gap-1 text-sm font-medium">Escribí REINICIAR para confirmar<input name="confirmation" required autocomplete="off" class="min-h-11 rounded-md border border-border bg-surface px-3"></label><div><x-ui.button type="submit" variant="danger">Archivar y reiniciar desde cero</x-ui.button></div></form></section>
        @endif

        @if ($canManage && ! $isSelf)
            <section class="rounded-lg border border-danger/40 bg-danger/5 p-5"><h2 class="font-heading text-lg font-bold text-danger-text">Zona sensible</h2><p class="mt-2 text-sm text-text-secondary">Anonimiza los datos personales, cierra sesiones y retira roles. Las matrículas, certificados y evidencias permanecen para preservar la trazabilidad educativa.</p><form method="POST" action="{{ route('users.anonymize', $managedUser['id']) }}" class="mt-4" onsubmit="return confirm('¿Anonimizar definitivamente esta cuenta? Esta acción no se puede deshacer.')">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger">Anonimizar cuenta</x-ui.button></form></section>
        @endif
    </div>
</x-layouts.app>
