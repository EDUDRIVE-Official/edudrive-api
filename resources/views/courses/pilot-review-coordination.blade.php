<x-layouts.app title="EDUDRIVE — Coordinación de revisiones P912">
    @php
        $specialties = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'];
        $scenes = ['van' => 'Van y visibilidad', 'turn' => 'Vehículo que gira', 'barrier' => 'Ruta interrumpida', 'descent' => 'Cambio de descenso'];
        $statusLabels = ['pending' => 'Pendiente', 'ready_for_specialist_review' => 'Sin cambios solicitados', 'changes_required' => 'Requiere cambios'];
        $totalExpected = count($reviewers) * count($scenes);
        $completed = collect($reviewers)->sum(fn ($reviewer) => $reviewer['progress']['completed']);
    @endphp

    <main id="pilot-main" class="mx-auto max-w-7xl space-y-8">
        <header class="space-y-3 border-b border-border pb-6">
            <p class="text-sm font-semibold text-primary">Operación interna · {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Coordinación de revisiones especializadas</h1>
            <p class="max-w-4xl text-text-secondary">Este tablero organiza el trabajo de personas ya designadas. Muestra registros reales guardados por cada cuenta; no permite que una persona administradora responda por ellas ni interpreta avance como aprobación.</p>
            <div class="flex flex-wrap gap-3"><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-reviewers') }}">Gestionar designaciones</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-review-summary') }}">Ver cobertura por escena</a><a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('pilot-instruments.visual-readiness') }}">Volver a preparación</a></div>
        </header>

        @if (! $mailDelivery['ready'])
            <section class="rounded-xl border border-amber-400 bg-amber-50 p-5 text-amber-950">
                <p class="text-sm font-semibold uppercase tracking-wide">Correo institucional · {{ $mailDelivery['completed'] }} de {{ $mailDelivery['total'] }} requisitos</p>
                <h2 class="mt-1 text-xl font-bold">Las invitaciones todavía no salen a internet</h2>
                <p class="mt-2">El transporte ya está preparado, pero EduDrive mantendrá la entrega externa deshabilitada hasta completar y validar toda la configuración.</p>
                <ul class="mt-4 grid gap-2 md:grid-cols-2">
                    @foreach ($mailDelivery['checks'] as $check)
                        <li class="rounded-lg bg-white/80 p-3"><span class="font-bold">{{ $check['passed'] ? '✓' : '•' }} {{ $check['label'] }}</span>@if (! $check['passed'])<p class="mt-1 text-sm">{{ $check['guidance'] }}</p>@endif</li>
                    @endforeach
                </ul>
                @if ($mailDelivery['uses_local_mailbox'])
                    <a class="mt-3 inline-flex min-h-11 items-center rounded border border-amber-700 px-4 font-semibold" href="{{ $mailDelivery['local_mailbox_url'] }}" target="_blank" rel="noopener">Abrir buzón local de pruebas</a>
                @endif
            </section>
        @else
            <section class="rounded-xl border border-green-400 bg-green-50 p-5 text-green-950"><strong>Correo institucional listo.</strong> Postmark, el remitente y los enlaces públicos están configurados para entrega externa.</section>
        @endif

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-border bg-surface p-5"><p class="text-sm text-text-secondary">Personas designadas</p><p class="mt-1 text-3xl font-bold">{{ count($reviewers) }} de 3</p><p class="text-sm">Una por especialidad requerida.</p></article>
            <article class="rounded-xl border border-border bg-surface p-5"><p class="text-sm text-text-secondary">Registros recibidos</p><p class="mt-1 text-3xl font-bold">{{ $completed }} de {{ $totalExpected }}</p><p class="text-sm">Cuatro escenas por persona.</p></article>
            <article class="rounded-xl border border-amber-400 bg-amber-50 p-5 text-amber-950"><p class="text-sm">Resultado actual</p><p class="mt-1 text-xl font-bold">{{ $completed === $totalExpected && $totalExpected > 0 ? 'Revisión completa' : 'Trabajo pendiente' }}</p><p class="text-sm">La cobertura no equivale a aval externo.</p></article>
        </section>

        <section class="space-y-4">
            <div><p class="text-sm font-semibold uppercase tracking-wide text-primary">Asignaciones activas</p><h2 class="text-2xl font-bold">Progreso por persona</h2></div>
            <div class="grid gap-5 lg:grid-cols-3">
                @forelse($reviewers as $reviewer)
                    <article class="space-y-4 rounded-2xl border border-border bg-surface p-5">
                        <div><p class="text-sm font-semibold text-primary">{{ $specialties[$reviewer['specialty']] }}</p><h3 class="text-xl font-bold">{{ $reviewer['name'] }}</h3><p class="text-sm text-text-secondary">{{ $reviewer['organization'] }} · {{ $reviewer['evidence_reference'] }}</p></div>
                        <div><div class="flex justify-between text-sm"><span>Progreso</span><strong>{{ $reviewer['progress']['completed'] }} de {{ $reviewer['progress']['total'] }}</strong></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full bg-primary" style="width: {{ $reviewer['progress']['total'] > 0 ? ($reviewer['progress']['completed'] / $reviewer['progress']['total']) * 100 : 0 }}%"></div></div></div>
                        <ul class="space-y-2">@foreach($scenes as $key => $label)@php($status = $reviewer['progress']['scenes'][$key])<li class="flex items-center justify-between gap-3 rounded border border-border p-3"><span>{{ $label }}</span><span class="text-sm font-semibold {{ $status === 'changes_required' ? 'text-red-700' : ($status === 'ready_for_specialist_review' ? 'text-green-700' : 'text-text-secondary') }}">{{ $statusLabels[$status] }}</span></li>@endforeach</ul>
                        <p class="rounded-lg {{ $reviewer['progress']['completed'] === 0 ? 'bg-amber-50 text-amber-950' : 'bg-blue-50 text-blue-950' }} p-3 text-sm"><strong>Siguiente acción:</strong> {{ $reviewer['progress']['completed'] === 0 ? 'Indicar el flujo de recuperación para que defina su propia contraseña e inicie la revisión.' : ($reviewer['progress']['completed'] < $reviewer['progress']['total'] ? 'Completar las escenas pendientes desde su propia cuenta.' : 'Esperar consolidación de hallazgos; no modificar sus respuestas.') }}</p>
                        <a class="inline-flex min-h-11 items-center rounded border border-border px-4 font-semibold" href="{{ route('users.show', $reviewer['user_id']) }}">Ver estado de la cuenta de {{ $reviewer['name'] }}</a>
                    </article>
                @empty
                    <p class="rounded border border-amber-400 bg-amber-50 p-4 text-amber-950">No existen designaciones activas. Incorporá y verificá personas antes de coordinar revisiones.</p>
                @endforelse
            </div>
        </section>

        <section class="space-y-4 rounded-2xl border border-blue-300 bg-blue-50 p-6 text-blue-950">
            <h2 class="text-2xl font-bold">Acceso personal sin compartir contraseñas</h2>
            <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['1', 'Abrir el acceso', 'La persona entra a la pantalla de inicio de sesión y elige “Olvidé mi contraseña o necesito crearla”.'],
                    ['2', 'Recibir enlace', 'EduDrive envía a su correo un enlace personal, de un solo uso y válido durante 60 minutos.'],
                    ['3', 'Crear su contraseña', 'Solo la persona revisora conoce la clave que define; después ingresa y abre la revisión de escenas.'],
                    ['4', 'Comprobar el tablero', 'El avance aparece aquí después de que la persona guarda cada criterio.'],
                ] as [$number, $title, $description])<li class="rounded-lg bg-white/80 p-4"><span class="font-bold">{{ $number }}. {{ $title }}</span><p class="mt-1">{{ $description }}</p></li>@endforeach
            </ol>
            <div class="flex flex-wrap items-center gap-3"><a class="inline-flex min-h-11 items-center rounded bg-primary px-4 font-semibold text-white" href="{{ route('password.request') }}">Abrir recuperación de acceso</a><p class="font-semibold">EduDrive no muestra ni distribuye contraseñas. El administrador tampoco las conoce.</p></div>
        </section>

        <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>Separación de funciones:</strong> la persona administradora coordina accesos y comprueba cobertura; cada especialista emite sus propios criterios. No completés formularios en nombre de otra persona.</aside>
    </main>
</x-layouts.app>
