<x-layouts.app title="EDUDRIVE — Operación del sistema">
    <div class="space-y-6">
        <div class="campus-heading flex flex-wrap items-start justify-between gap-4">
            <div><h1 class="font-heading text-2xl font-bold">Salud y auditoría</h1><p class="mt-1 text-sm text-text-secondary">Estado técnico y actividad reciente del sistema.</p></div>
            <a href="{{ route('admin.system.summary') }}" class="inline-flex min-h-[44px] items-center rounded-sm border border-border px-4 text-sm font-medium">Volver al resumen</a>
        </div>
        @if (session('status'))<p class="text-sm text-success-text">{{ session('status') }}</p>@endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.card>
                <div class="flex items-center justify-between"><h2 class="font-heading text-lg font-bold">Estado general</h2><x-ui.badge :variant="$health['status'] === 'healthy' ? 'success' : 'danger'">{{ $health['status'] === 'healthy' ? 'Saludable' : 'Con problemas' }}</x-ui.badge></div>
                <p class="mt-3 text-sm text-text-secondary">Última revisión: {{ \Illuminate\Support\Carbon::parse($health['checked_at'])->format('d/m/Y H:i:s') }}</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center justify-between"><h2 class="font-heading text-lg font-bold">Base de datos</h2><x-ui.badge :variant="$health['database'] === 'up' ? 'success' : 'danger'">{{ $health['database'] === 'up' ? 'Disponible' : 'No disponible' }}</x-ui.badge></div>
            </x-ui.card>
        </div>
        <x-ui.card>
            @php
                $actionLabels = [
                    'identity.user_activated' => 'Usuario activado',
                    'identity.user_deactivated' => 'Usuario desactivado',
                    'identity.user_logged_in' => 'Inicio de sesión',
                    'identity.user_login_failed' => 'Intento de ingreso fallido',
                    'identity.guardian_relationship_created' => 'Acompañamiento creado',
                    'identity.guardian_relationship_revoked' => 'Acompañamiento revocado',
                    'identity.guardian_practice_observed' => 'Práctica acompañada registrada',
                    'identity.admin_user_created' => 'Usuario creado por administración',
                    'identity.admin_user_updated' => 'Datos del usuario actualizados',
                    'identity.admin_temporary_password_issued' => 'Contraseña temporal generada',
                    'identity.account_anonymized' => 'Cuenta anonimizada',
                    'identity.account_activated' => 'Cuenta activada',
                    'identity.account_deactivated' => 'Cuenta desactivada',
                    'identity.account_deleted' => 'Cuenta eliminada',
                    'authorization.role_assigned' => 'Rol asignado',
                    'authorization.role_revoked' => 'Rol retirado',
                    'auth.login' => 'Inicio de sesión',
                    'auth.logout' => 'Cierre de sesión',
                    'auth.logout_all' => 'Cierre de todas las sesiones',
                    'auth.password_reset' => 'Contraseña restablecida',
                    'auth.password_reset_requested' => 'Restablecimiento de contraseña solicitado',
                    'auth.email_verified' => 'Correo electrónico verificado',
                    'auth.email_verification_requested' => 'Verificación de correo solicitada',
                    'auth.session_revoked' => 'Sesión cerrada por seguridad',
                    'admin.system_setting_changed' => 'Configuración del sistema actualizada',
                    'export.audit_logs' => 'Exportación de auditoría solicitada',
                    'export.enrollments' => 'Exportación de matrículas solicitada',
                    'export.courses' => 'Exportación de cursos solicitada',
                    'road_passport.student_reflection_recorded' => 'Reflexión vial registrada',
                    'road_passport.personal_practice_recorded' => 'Práctica vial personal registrada',
                    'road_passport.verification_link_rotated' => 'Enlace de verificación renovado',
                    'integration.api_consumer_registered' => 'Integración externa registrada',
                    'integration.api_consumer_suspended' => 'Integración externa suspendida',
                    'integration.api_consumer_reactivated' => 'Integración externa reactivada',
                    'integration.api_consumer_key_rotated' => 'Llave de integración renovada',
                    'integration.api_consumer_revoked' => 'Integración externa revocada',
                    'webhook.subscription_registered' => 'Webhook registrado',
                    'webhook.subscription_suspended' => 'Webhook suspendido',
                    'webhook.subscription_reactivated' => 'Webhook reactivado',
                    'webhook.subscription_secret_rotated' => 'Secreto del webhook renovado',
                ];
                $outcomeLabels = ['success' => 'Correcto', 'failure' => 'Falló', 'failed' => 'Falló', 'denied' => 'Denegado'];
                $entityLabels = ['User' => 'Usuario', 'GuardianRelationship' => 'Acompañamiento', 'Lesson' => 'Lección', 'RoleAssignment' => 'Asignación de rol', 'SystemSetting' => 'Configuración', 'ApiConsumer' => 'Integración externa', 'WebhookSubscription' => 'Webhook', 'RoadPassport' => 'Pasaporte vial'];
            @endphp
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-heading text-lg font-bold">Registro de auditoría</h2><p class="text-sm text-text-secondary">{{ count($logs) }} eventos registrados</p></div>
                <form method="POST" action="{{ route('admin.system.audit.export') }}">@csrf <x-ui.button type="submit" variant="secondary">Exportar CSV</x-ui.button></form>
            </div>
            <x-ui.table>
                <x-slot:head><tr><th class="px-4 py-2">Fecha</th><th class="px-4 py-2">Acción</th><th class="px-4 py-2">Entidad</th><th class="px-4 py-2">Resultado</th><th class="px-4 py-2">Responsable</th></tr></x-slot:head>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2 text-sm">{{ $log['occurred_at'] ? \Illuminate\Support\Carbon::parse($log['occurred_at'])->format('d/m/Y H:i') : '—' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $actionLabels[$log['action']] ?? 'Actividad del sistema' }}</td>
                        <td class="px-4 py-2 text-sm">{{ isset($log['entity']) ? ($entityLabels[$log['entity']] ?? 'Registro') : '—' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $outcomeLabels[$log['outcome']] ?? 'Procesado' }}</td>
                        <td class="px-4 py-2 text-sm"><strong>{{ $log['actor_name'] }}</strong>@if ($log['actor_email'])<br><span class="text-xs text-text-secondary">{{ $log['actor_email'] }}</span>@endif @if ($log['ip'])<br><span class="text-xs text-text-secondary">Origen: {{ $log['ip'] }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-text-secondary">No hay eventos de auditoría.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.app>
