<x-layouts.design-system title="EDUDRIVE — Design System">
    <div class="flex flex-col gap-10">
        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Botones</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">Primario</x-ui.button>
                <x-ui.button variant="secondary">Secundario</x-ui.button>
                <x-ui.button variant="danger">Peligro</x-ui.button>
                <x-ui.button variant="primary" size="sm">Chico</x-ui.button>
                <x-ui.button variant="primary" size="lg">Grande</x-ui.button>
                <x-ui.button variant="primary" :disabled="true">Deshabilitado</x-ui.button>
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Inputs</h2>
            <div class="flex max-w-sm flex-col gap-4">
                <x-ui.input name="nombre" label="Nombre" placeholder="Escuela de Manejo EDUDRIVE" />
                <x-ui.input name="correo" label="Correo" type="email" error="Este campo es obligatorio." />
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Cards</h2>
            <x-ui.card class="max-w-sm">
                <p class="font-sans text-text">Contenido de ejemplo dentro de una card.</p>
            </x-ui.card>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Badges</h2>
            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="success">Activa</x-ui.badge>
                <x-ui.badge variant="info">Info</x-ui.badge>
                <x-ui.badge variant="warning">Pendiente</x-ui.badge>
                <x-ui.badge variant="danger">Inactiva</x-ui.badge>
            </div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Etapas</h2>
            <p class="mb-4 text-text-secondary">Cada etapa tiene forma y etiqueta propias: el color nunca es la única señal.</p>
            <ul class="flex flex-wrap items-end gap-8">
                @foreach (['E1' => ['DESCUBRO', '3–6 años'], 'E2' => ['COMPRENDO', '7–12 años'], 'E3' => ['DECIDO', '13–16 años'], 'E4' => ['CONDUZCO', '17+ años']] as $code => [$name, $ages])
                    <li class="flex flex-col items-center gap-2 text-center">
                        <x-ui.stage-plate :stage="$code" size="lg" />
                        <span class="font-heading text-xl font-extrabold">{{ $name }}</span>
                        <span class="text-sm text-text-secondary">{{ $ages }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6" aria-hidden="true"><span class="ed-cebra"></span></div>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Iconos</h2>
            <ul class="flex flex-wrap gap-4">
                @foreach (['user', 'book', 'passport', 'certificate', 'progress', 'bell', 'more', 'admin', 'sun', 'moon', 'check', 'x', 'warning', 'info', 'search', 'edit', 'clock', 'organization', 'family'] as $icon)
                    <li class="flex w-24 flex-col items-center gap-1 text-center text-sm text-text-secondary">
                        <x-ui.icon :name="$icon" size="lg" class="text-text" />
                        <span>{{ $icon }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section>
            <h2 class="mb-4 font-heading text-xl font-semibold">Tabla</h2>
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-4 py-2">Organización</th>
                        <th scope="col" class="px-4 py-2">Tipo</th>
                        <th scope="col" class="px-4 py-2">Estado</th>
                    </tr>
                </x-slot:head>
                <tr>
                    <td class="px-4 py-2">Escuela de Manejo EDUDRIVE</td>
                    <td class="px-4 py-2">Escuela de manejo</td>
                    <td class="px-4 py-2"><x-ui.badge variant="success">Activa</x-ui.badge></td>
                </tr>
            </x-ui.table>
        </section>
    </div>
</x-layouts.design-system>
