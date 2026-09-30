<x-layouts.app title="EDUDRIVE — Mi perfil">
    <div class="profile-campus mx-auto flex max-w-4xl flex-col gap-6">
        @php
            $initials = collect(preg_split('/\s+/', trim($profile['name'])))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
        @endphp
        <section class="campus-hero relative overflow-hidden rounded-xl bg-[#0b3a6e] px-6 py-7 text-white shadow-md sm:px-8" aria-labelledby="profile-title">
            <div class="absolute -right-12 -top-16 h-48 w-48 rounded-full bg-[#008a78]/45" aria-hidden="true"></div>
            <div class="absolute -bottom-20 right-28 h-40 w-40 rounded-full bg-[#f5b700]/20" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border-4 border-white/40 bg-white/15 font-heading text-2xl font-bold" aria-hidden="true">{{ $initials }}</div>
                <div>
                    <p class="text-sm font-semibold text-white/80">Tu espacio personal</p>
                    <h1 id="profile-title" class="mt-1 font-heading text-3xl font-bold">Hola, {{ $profile['name'] }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/85">Desde aquí podés continuar aprendiendo, revisar tus evidencias y ver cómo crece tu recorrido vial.</p>
                </div>
                <img src="{{ asset('brand/mobility-campus.svg') }}" class="campus-hero-art" alt="" aria-hidden="true">
            </div>
        </section>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        @include('descubro.entry')

        <section class="grid gap-4 sm:grid-cols-3" aria-label="Accesos principales">
            <a href="{{ route('courses.index') }}" class="group rounded-xl border border-border bg-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary hover:shadow-md focus-visible:outline-none focus-visible:shadow-focus">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-xl" aria-hidden="true">📘</span>
                <h2 class="mt-4 font-heading font-bold group-hover:text-primary">Continuar aprendiendo</h2>
                <p class="mt-1 text-sm leading-5 text-text-secondary">Explorá tus cursos y retomá la siguiente misión.</p>
            </a>
            <a href="{{ route('road-passport.show') }}" class="group rounded-xl border border-border bg-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-secondary hover:shadow-md focus-visible:outline-none focus-visible:shadow-focus">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-secondary/10 text-xl" aria-hidden="true">🛂</span>
                <h2 class="mt-4 font-heading font-bold group-hover:text-secondary">Mi Pasaporte Vial</h2>
                <p class="mt-1 text-sm leading-5 text-text-secondary">Consultá las experiencias y evidencias de tu recorrido.</p>
            </a>
            <a href="{{ route('gamification.dashboard') }}" class="group rounded-xl border border-border bg-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-accent hover:shadow-md focus-visible:outline-none focus-visible:shadow-focus">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-accent/15 text-xl" aria-hidden="true">🧭</span>
                <h2 class="mt-4 font-heading font-bold group-hover:text-primary">Ver mi progreso</h2>
                <p class="mt-1 text-sm leading-5 text-text-secondary">Reconocé avances, prácticas, insignias y próximos pasos.</p>
            </a>
        </section>

        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-heading text-lg font-bold">Información personal</h2>
                    <p class="mt-1 text-sm text-text-secondary">{{ $profile['date_of_birth'] ? 'Fecha de nacimiento: '.$profile['date_of_birth'] : 'Completá tu fecha de nacimiento para recibir recomendaciones apropiadas para tu etapa.' }}</p>
                </div>
                @if ($profile['is_minor'])<x-ui.badge variant="warning">Aprendizaje con acompañamiento</x-ui.badge>@endif
            </div>
        </x-ui.card>

        <section class="rounded-xl border border-border bg-surface p-5 shadow-sm" aria-labelledby="profile-organizations-title">
            <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Comunidad educativa</p><h2 id="profile-organizations-title" class="mt-1 font-heading text-lg font-bold">Mis organizaciones</h2><p class="mt-1 text-sm leading-6 text-text-secondary">Vinculate con tu escuela, colegio u organización para acceder a sus grupos y experiencias asignadas.</p></div>

            @if (count($profile['organizations']) > 0)
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($profile['organizations'] as $organization)
                        <div class="flex items-center gap-3 rounded-lg border border-success/25 bg-success/5 p-3"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-success/10" aria-hidden="true">🏫</span><div><p class="font-bold text-text">{{ $organization['name'] }}</p><p class="text-xs font-medium text-success-text">Membresía activa</p></div></div>
                    @endforeach
                </div>
            @endif

            @if (count($profile['organization_requests']) > 0)
                <div class="mt-4 space-y-2">
                    @foreach ($profile['organization_requests'] as $organizationRequest)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-warning/30 bg-safety/10 p-3"><div><p class="font-bold text-text">{{ $organizationRequest['organization_name'] }}</p><p class="text-xs text-warning-text">Solicitud pendiente desde {{ $organizationRequest['requested_at'] }}</p></div><form method="POST" action="{{ route('student-profile.organizations.cancel', $organizationRequest['id']) }}">@csrf @method('DELETE')<button type="submit" class="min-h-10 rounded-md border border-border px-3 text-xs font-bold text-text hover:bg-background">Cancelar solicitud</button></form></div>
                    @endforeach
                </div>
            @endif

            @if (count($profile['available_organizations']) > 0)
                <form method="POST" action="{{ route('student-profile.organizations.request') }}" class="mt-5 rounded-lg border border-primary/25 bg-primary/5 p-4">
                    @csrf
                    <label for="organization_id" class="text-sm font-bold text-text">Buscar una organización existente</label>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <select id="organization_id" name="organization_id" required class="min-h-11 flex-1 rounded-md border border-border bg-surface px-3 text-base text-text focus-visible:outline-none focus-visible:shadow-focus">
                            <option value="">Seleccioná tu escuela u organización</option>
                            @foreach ($profile['available_organizations'] as $organization)<option value="{{ $organization['id'] }}">{{ $organization['name'] }}</option>@endforeach
                        </select>
                        <button type="submit" class="min-h-11 rounded-md bg-primary px-5 text-sm font-bold text-white hover:bg-secondary">Solicitar vinculación</button>
                    </div>
                    @error('organization_id')<p class="mt-2 text-sm text-danger-text">{{ $message }}</p>@enderror
                    @if (session('organization_error'))<p class="mt-2 text-sm text-warning-text">{{ session('organization_error') }}</p>@endif
                    <p class="mt-2 text-xs leading-5 text-text-secondary">La organización debe confirmar que pertenecés a ella. Esta solicitud nunca otorga permisos administrativos.</p>
                </form>
            @elseif (count($profile['organizations']) === 0 && count($profile['organization_requests']) === 0)
                <p class="mt-4 rounded-lg border border-dashed border-border bg-background p-4 text-sm text-text-secondary">No hay organizaciones disponibles para solicitar en este momento.</p>
            @endif
        </section>

        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Acompañamiento familiar</h2>
            @if ($profile['is_minor'])
                @if (count($profile['guardians']) > 0)
                    <p class="mt-1 text-sm text-text-secondary">Tenés acompañamiento vinculado para las prácticas que requieren supervisión.</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($profile['guardians'] as $guardian)<li>✓ {{ $guardian }}</li>@endforeach
                    </ul>
                @else
                    <p class="mt-1 text-sm leading-6 text-warning-text">Todavía no hay una persona adulta vinculada. Podés avanzar en pantalla, pero las prácticas fuera de ella deben esperar acompañamiento seguro.</p>
                @endif
            @elseif (count($profile['linked_minors']) > 0)
                <p class="mt-1 text-sm text-text-secondary">Personas menores cuyo aprendizaje acompañás:</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($profile['linked_minors'] as $minor)<li>✓ {{ $minor }}</li>@endforeach
                </ul>
            @else
                <p class="mt-1 text-sm text-text-secondary">No tenés relaciones de acompañamiento activas.</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Pasaporte vial</h2>
            @if ($profile['road_passport'])
                <p class="font-sans text-sm text-text">Estado: {{ ['active' => 'Activo', 'suspended' => 'Suspendido', 'revoked' => 'Revocado'][$profile['road_passport']['status']] ?? 'No disponible' }}</p>
                <p class="font-sans text-sm text-text">Nivel: {{ $profile['road_passport']['level'] }}</p>
                <p class="font-sans text-sm text-text-secondary">Emitido: {{ $profile['road_passport']['issued_at'] }}</p>
            @else
                <p class="font-sans text-sm text-text-secondary">Todavía no tenés un pasaporte vial emitido.</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Mis matrículas</h2>
            @if (count($profile['enrollments']) > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-4 py-2">Curso</th>
                            <th scope="col" class="px-4 py-2">Estado</th>
                            <th scope="col" class="px-4 py-2">Fecha de matrícula</th>
                            <th scope="col" class="px-4 py-2">Actividad</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($profile['enrollments'] as $enrollment)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $enrollment['course_title'] }}</td>
                            <td class="px-4 py-2">{{ ['active' => 'En curso', 'completed' => 'Completado', 'pending' => 'Pendiente', 'canceled' => 'Cancelado'][$enrollment['status']] ?? $enrollment['status'] }}</td>
                            <td class="px-4 py-2">{{ $enrollment['enrolled_at'] }}</td>
                            <td class="px-4 py-2"><a href="{{ route('learning-events.show', $enrollment['enrollment_id']) }}" class="text-primary hover:underline">Ver actividad</a></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @else
                <p class="font-sans text-sm text-text-secondary">Todavía no tenés matrículas.</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Editar mi perfil</h2>
            <form method="POST" action="{{ route('student-profile.update') }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <x-ui.input
                    name="date_of_birth"
                    type="date"
                    label="Fecha de nacimiento"
                    value="{{ old('date_of_birth', $profile['date_of_birth']) }}"
                    :error="$errors->first('date_of_birth')"
                />
                <p class="-mt-3 text-xs leading-5 text-text-secondary">Se utiliza para adaptar la etapa educativa y las recomendaciones. No cambia los requisitos de seguridad.</p>

                <x-ui.input
                    name="education_level"
                    label="Nivel educativo"
                    value="{{ old('education_level', $profile['education_level']) }}"
                    :error="$errors->first('education_level')"
                />

                <div class="flex flex-col gap-1">
                    <label for="accessibility_needs" class="font-sans text-sm font-medium text-text">Necesidades de accesibilidad</label>
                    <textarea
                        id="accessibility_needs"
                        name="accessibility_needs"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('accessibility_needs', $profile['accessibility_needs']) }}</textarea>
                    @error('accessibility_needs')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="learning_preferences" class="font-sans text-sm font-medium text-text">Preferencias de aprendizaje</label>
                    <textarea
                        id="learning_preferences"
                        name="learning_preferences"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('learning_preferences', $profile['learning_preferences']) }}</textarea>
                    @error('learning_preferences')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.button type="submit" variant="primary">Guardar</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
