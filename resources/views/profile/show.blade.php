<x-layouts.app title="EDUDRIVE — Mi perfil">
    <div class="ed-perfil mx-auto max-w-6xl">
        @php
            $initials = collect(preg_split('/\s+/', trim($profile['name'])))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
            $route = $profile['curricular_route'];
        @endphp
        <section class="campus-hero px-6 py-7 text-white sm:px-8" aria-labelledby="profile-title">
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="ed-avatar" aria-hidden="true">{{ $initials }}</div>
                <div>
                    <p class="text-base font-semibold text-white/80">Tu espacio personal</p>
                    <h1 id="profile-title" class="mt-1">Hola, {{ $profile['name'] }}</h1>
                    <p class="mt-2 max-w-xl text-lg leading-6 text-white/85">Desde aquí podés continuar aprendiendo, revisar tus evidencias y ver cómo crece tu recorrido vial.</p>
                </div>
                <img src="{{ asset('brand/mobility-campus.svg') }}" class="campus-hero-art" alt="" aria-hidden="true">
            </div>
        </section>

        @if (session('status'))
            <p class="ed-ancho font-sans text-base font-bold text-success-text" role="status">{{ session('status') }}</p>
        @endif

        <div class="ed-col">
            <section class="campus-card ed-recorrido" aria-label="Mi recorrido curricular">
                @if (in_array($route['stage'] ?? null, ['E1', 'E2', 'E3', 'E4'], true))
                    <x-ui.stage-plate :stage="$route['stage']" size="lg" />
                @endif
                <div>
                    <h2>Mi recorrido: {{ $route['identity'] }}</h2>
                    <p class="text-lg">{{ $route['age_range'] }} · {{ $route['guidance'] }}</p>
                    <a href="{{ route('courses.index') }}" class="ed-enlace">Ver mi recorrido y cursos disponibles</a>
                </div>
            </section>

            @include('descubro.entry')

            <ul class="ed-accesos" aria-label="Accesos principales">
                <li>
                    <a href="{{ route('courses.index') }}" class="ed-acceso ed-acceso--principal">
                        <strong><x-ui.icon name="book" size="lg" />Continuar aprendiendo</strong>
                        <span>Explorá tus cursos y retomá la siguiente misión.</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('road-passport.show') }}" class="ed-acceso">
                        <strong><x-ui.icon name="passport" size="lg" />Mi Pasaporte Vial</strong>
                        <span>Consultá las experiencias y evidencias de tu recorrido.</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('gamification.dashboard') }}" class="ed-acceso">
                        <strong><x-ui.icon name="progress" size="lg" />Ver mi progreso</strong>
                        <span>Reconocé avances, prácticas, insignias y próximos pasos.</span>
                    </a>
                </li>
            </ul>

            <section class="campus-card" aria-labelledby="profile-organizations-title">
                <div>
                    <p class="text-base font-bold text-text-secondary">Comunidad educativa</p>
                    <h2 id="profile-organizations-title" class="mt-1 text-3xl">Mis organizaciones</h2>
                    <p class="mt-1 text-lg leading-6 text-text-secondary">Vinculate con tu escuela, colegio u organización para acceder a sus grupos y experiencias asignadas.</p>
                </div>

                @if (count($profile['organizations']) > 0)
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($profile['organizations'] as $organization)
                            <div class="ed-org"><x-ui.icon name="organization" size="lg" /><div><strong>{{ $organization['name'] }}</strong><p class="m-0 inline-flex items-center gap-1 text-base font-semibold text-success-text"><x-ui.icon name="check" size="sm" />Membresía activa</p></div></div>
                        @endforeach
                    </div>
                @endif

                @if (count($profile['organization_requests']) > 0)
                    <div class="mt-4 space-y-3">
                        @foreach ($profile['organization_requests'] as $organizationRequest)
                            <div class="ed-aviso flex-wrap items-center justify-between"><div><p class="m-0 font-bold text-text">{{ $organizationRequest['organization_name'] }}</p><p class="m-0 text-base text-text">Solicitud pendiente desde {{ $organizationRequest['requested_at'] }}</p></div><form method="POST" action="{{ route('student-profile.organizations.cancel', $organizationRequest['id']) }}">@csrf @method('DELETE')<button type="submit" class="ed-btn ed-btn--claro">Cancelar solicitud</button></form></div>
                        @endforeach
                    </div>
                @endif

                @if (count($profile['available_organizations']) > 0)
                    <form method="POST" action="{{ route('student-profile.organizations.request') }}" class="mt-5 rounded-sm border-[3px] border-border-strong bg-background p-4">
                        @csrf
                        <label for="organization_id" class="text-base font-bold text-text">Buscar una organización existente</label>
                        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                            <select id="organization_id" name="organization_id" required class="ed-control flex-1">
                                <option value="">Seleccioná tu escuela u organización</option>
                                @foreach ($profile['available_organizations'] as $organization)<option value="{{ $organization['id'] }}">{{ $organization['name'] }}</option>@endforeach
                            </select>
                            <button type="submit" class="ed-btn ed-btn--negro">Solicitar vinculación</button>
                        </div>
                        @error('organization_id')<p class="mt-2 text-base font-semibold text-danger-text">{{ $message }}</p>@enderror
                        @if (session('organization_error'))<p class="mt-2 text-base font-semibold text-warning-text">{{ session('organization_error') }}</p>@endif
                        <p class="ed-mini mt-2">La organización debe confirmar que pertenecés a ella. Esta solicitud nunca otorga permisos administrativos.</p>
                    </form>
                @elseif (count($profile['organizations']) === 0 && count($profile['organization_requests']) === 0)
                    <p class="ed-vacio mt-4 text-lg text-text-secondary">No hay organizaciones disponibles para solicitar en este momento.</p>
                @endif
            </section>

            <x-ui.card>
                <h2 class="text-3xl">Acompañamiento familiar</h2>
                @if ($profile['is_minor'])
                    @if (count($profile['guardians']) > 0)
                        <p class="mt-1 text-lg text-text-secondary">Tenés acompañamiento vinculado para las prácticas que requieren supervisión.</p>
                        <ul class="mt-3 space-y-2 text-lg">
                            @foreach ($profile['guardians'] as $guardian)<li class="flex items-center gap-2"><x-ui.icon name="check" size="sm" />{{ $guardian }}</li>@endforeach
                        </ul>
                    @else
                        <p class="ed-aviso mt-3 text-lg leading-6 text-text"><x-ui.icon name="warning" />Todavía no hay una persona adulta vinculada. Podés avanzar en pantalla, pero las prácticas fuera de ella deben esperar acompañamiento seguro.</p>
                    @endif
                @elseif (count($profile['linked_minors']) > 0)
                    <p class="mt-1 text-lg text-text-secondary">Personas menores cuyo aprendizaje acompañás:</p>
                    <ul class="mt-3 space-y-2 text-lg">
                        @foreach ($profile['linked_minors'] as $minor)<li class="flex items-center gap-2"><x-ui.icon name="check" size="sm" />{{ $minor }}</li>@endforeach
                    </ul>
                @else
                    <p class="mt-1 text-lg text-text-secondary">No tenés relaciones de acompañamiento activas.</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h2 class="text-3xl">Pasaporte vial</h2>
                @if ($profile['road_passport'])
                    <p class="font-sans text-lg text-text">Estado: {{ ['active' => 'Activo', 'suspended' => 'Suspendido', 'revoked' => 'Revocado'][$profile['road_passport']['status']] ?? 'No disponible' }}</p>
                    <p class="font-sans text-lg text-text">Nivel: {{ $profile['road_passport']['level'] }}</p>
                    <p class="font-sans text-lg text-text-secondary">Emitido: {{ $profile['road_passport']['issued_at'] }}</p>
                @else
                    <p class="font-sans text-lg text-text-secondary">Todavía no tenés un pasaporte vial emitido.</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h2 class="text-3xl">Mis matrículas</h2>
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
                                <td class="px-4 py-2"><a href="{{ route('learning-events.show', $enrollment['enrollment_id']) }}" class="ed-enlace">Ver actividad</a></td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @else
                    <p class="font-sans text-lg text-text-secondary">Todavía no tenés matrículas.</p>
                @endif
            </x-ui.card>
        </div>

        <aside class="ed-col" aria-label="Datos personales y edición del perfil">
            <x-ui.card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-3xl">Información personal</h2>
                        <p class="mt-1 text-lg text-text-secondary">{{ $profile['date_of_birth'] ? 'Fecha de nacimiento: '.$profile['date_of_birth'] : 'Completá tu fecha de nacimiento para recibir recomendaciones apropiadas para tu etapa.' }}</p>
                    </div>
                    @if ($profile['is_minor'])<x-ui.badge variant="warning">Aprendizaje con acompañamiento</x-ui.badge>@endif
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="text-3xl">Editar mi perfil</h2>
                <form method="POST" action="{{ route('student-profile.update') }}" class="mt-3 flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <x-ui.input
                        name="date_of_birth"
                        type="date"
                        label="Fecha de nacimiento"
                        value="{{ old('date_of_birth', $profile['date_of_birth']) }}"
                        :error="$errors->first('date_of_birth')"
                    />
                    <p class="ed-mini -mt-3">Se utiliza para adaptar la etapa educativa y las recomendaciones. No cambia los requisitos de seguridad.</p>

                    <div>
                        <label for="learning_purpose" class="block text-base font-bold">Propósito del recorrido 17+</label>
                        <select id="learning_purpose" name="learning_purpose" class="ed-control mt-2">
                            <option value="">Por definir</option>
                            @foreach (['mobility' => 'Movilidad cotidiana (peatón, pasajero y ciclismo)', 'auto' => 'Aprender o actualizar conducción de automóvil', 'motorcycle' => 'Aprender o actualizar conducción de motocicleta'] as $purpose => $label)
                                <option value="{{ $purpose }}" @selected(old('learning_purpose', $profile['learning_purpose'] ?? null) === $purpose)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="ed-mini mt-2">Se utiliza desde los 17 años. No exige cursar etapas infantiles ni equivale a una licencia. Podés cambiarlo sin perder avances.</p>
                        @error('learning_purpose')<p class="text-base font-semibold text-danger-text">{{ $message }}</p>@enderror
                    </div>

                    <x-ui.input
                        name="education_level"
                        label="Nivel educativo"
                        value="{{ old('education_level', $profile['education_level']) }}"
                        :error="$errors->first('education_level')"
                    />

                    <div class="flex flex-col gap-1">
                        <label for="accessibility_needs" class="font-sans text-base font-bold text-text">Necesidades de accesibilidad</label>
                        <textarea
                            id="accessibility_needs"
                            name="accessibility_needs"
                            rows="3"
                            class="ed-control"
                        >{{ old('accessibility_needs', $profile['accessibility_needs']) }}</textarea>
                        @error('accessibility_needs')
                            <p class="font-sans text-base font-semibold text-danger-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="learning_preferences" class="font-sans text-base font-bold text-text">Preferencias de aprendizaje</label>
                        <textarea
                            id="learning_preferences"
                            name="learning_preferences"
                            rows="3"
                            class="ed-control"
                        >{{ old('learning_preferences', $profile['learning_preferences']) }}</textarea>
                        @error('learning_preferences')
                            <p class="font-sans text-base font-semibold text-danger-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" variant="primary" size="lg" class="w-full">Guardar</x-ui.button>
                </form>
            </x-ui.card>
        </aside>
    </div>
</x-layouts.app>
