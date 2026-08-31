<x-layouts.app title="EDUDRIVE — Mi perfil">
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Mi perfil</h1>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.card>
            <div class="flex flex-col gap-2">
                <p class="font-sans text-base text-text"><strong>{{ $profile['name'] }}</strong></p>
                @if ($profile['date_of_birth'])
                    <p class="font-sans text-sm text-text-secondary">Fecha de nacimiento: {{ $profile['date_of_birth'] }}</p>
                @endif
                @if ($profile['is_minor'])
                    <x-ui.badge variant="warning">Menor de edad</x-ui.badge>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-heading text-lg font-bold">Pasaporte vial</h2>
            @if ($profile['road_passport'])
                <p class="font-sans text-sm text-text">Estado: {{ $profile['road_passport']['status'] }}</p>
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
                        </tr>
                    </x-slot:head>
                    @foreach ($profile['enrollments'] as $enrollment)
                        <tr>
                            <td class="px-4 py-2">{{ $enrollment['course_id'] }}</td>
                            <td class="px-4 py-2">{{ $enrollment['status'] }}</td>
                            <td class="px-4 py-2">{{ $enrollment['enrolled_at'] }}</td>
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
