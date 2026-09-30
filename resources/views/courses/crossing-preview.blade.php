<x-layouts.app title="EDUDRIVE — Prueba de cruce 3D">
    <div class="mx-auto max-w-5xl space-y-4">
        <h1 class="font-heading text-2xl font-bold">Misión Camino Seguro: prueba visual</h1>
        <p class="text-text-secondary">Primera escena 3D para evaluar el cruce seguro. Explorá los cinco pasos y compará las dos cámaras.</p>
        @include('courses.blocks.module-scene', ['module' => ['code' => 'MISION-CRUCE']])
    </div>
</x-layouts.app>
