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
