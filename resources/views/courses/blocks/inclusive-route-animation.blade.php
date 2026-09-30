<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="routeComparison3d">
    <header class="border-b border-border px-4 py-3 sm:px-5">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">Mapa interactivo · 3D</p>
        <h4 class="mt-1 font-heading text-lg font-bold">Compará dos rutas</h4>
        <p class="mt-1 text-sm text-text-secondary">Observá el recorrido completo: no basta con que una ruta sea más corta; también debe ser continua, visible y accesible.</p>
    </header>

    <div class="relative overflow-hidden bg-sky-100" style="height:clamp(320px,50vw,520px)" x-ref="viewport">
        <div x-show="!ready" class="absolute inset-0 grid place-items-center bg-gradient-to-b from-sky-100 to-emerald-50 px-5 text-center">
            <div class="max-w-md">
                <div aria-hidden="true" class="text-5xl">🏫 <span class="mx-4 text-3xl text-primary">→</span> 🏠</div>
                <p class="mt-4 font-bold text-slate-900">Explorá el trayecto entre la escuela y la casa</p>
                <p class="mt-2 text-sm text-slate-700">La escena mostrará aceras, tránsito, una obra, rampas y un paso peatonal.</p>
                <button type="button" class="mt-4 min-h-11 rounded-md bg-primary px-5 text-sm font-bold text-white disabled:opacity-60" @click="open" :disabled="loading">
                    <span x-text="loading ? 'Preparando mapa…' : 'Abrir mapa 3D'"></span>
                </button>
            </div>
        </div>
        <div x-show="ready" x-cloak class="pointer-events-none absolute left-3 top-3 rounded-full bg-slate-900/85 px-3 py-2 text-xs font-bold text-white shadow-lg sm:text-sm">
            <span x-show="route === 'none'">Elegí una ruta para comenzar</span>
            <span x-show="route === 'short'">Ruta corta: aparece un conflicto con el tránsito</span>
            <span x-show="route === 'safe'">Ruta protegida: acera, rampa y paso visible</span>
        </div>
        <div x-show="ready && view === 'overview'" x-cloak class="pointer-events-none absolute bottom-3 right-3 rounded-md bg-slate-900/80 px-3 py-2 text-xs text-white">Arrastrá para observar · rueda para acercar</div>
        <div x-show="ready && view === 'traveler'" x-cloak class="pointer-events-none absolute bottom-3 right-3 rounded-md bg-slate-900/80 px-3 py-2 text-xs text-white">La cámara acompaña a la persona</div>
    </div>

    <div class="p-4 sm:p-5">
        <p class="text-sm text-text-secondary" aria-live="polite"
           x-text="route === 'safe' ? 'La ruta protegida es un poco más larga, pero mantiene a la persona sobre la acera y utiliza una rampa y un paso peatonal visibles.' : route === 'short' ? 'La obra interrumpe la acera. La ruta corta obliga a acercarse a vehículos y termina en un cruce sin protección.' : 'Elegí una ruta y seguí a la persona durante todo el recorrido.'"></p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" class="min-h-11 rounded-md border border-danger px-4 text-sm font-bold text-danger disabled:opacity-60" @click="choose('short')" :disabled="loading">Recorrer ruta corta</button>
            <button type="button" class="min-h-11 rounded-md bg-primary px-4 text-sm font-bold text-white disabled:opacity-60" @click="choose('safe')" :disabled="loading">Recorrer ruta protegida</button>
            <button x-show="ready && route !== 'none'" x-cloak type="button" class="min-h-11 rounded-md border border-border px-4 text-sm font-bold" @click="togglePause" x-text="paused ? 'Continuar' : 'Pausar'"></button>
            <button x-show="ready && route !== 'none'" x-cloak type="button" class="min-h-11 rounded-md border border-border px-4 text-sm font-bold" @click="replay">Repetir</button>
        </div>
        <div x-show="ready" x-cloak class="mt-3 flex flex-wrap items-center gap-3">
            <label class="text-sm font-bold" for="route-camera">Vista</label>
            <select id="route-camera" class="min-h-11 rounded-md border border-border bg-surface px-3 text-sm" x-model="view" @change="changeView($event.target.value)">
                <option value="overview">Mapa completo</option>
                <option value="traveler">Acompañar a la persona</option>
            </select>
            <button type="button" class="min-h-11 text-sm font-bold text-text-secondary underline" @click="close">Cerrar mapa 3D</button>
        </div>
        <div x-show="route !== 'none'" x-cloak class="mt-4 grid gap-2 sm:grid-cols-3" aria-live="polite">
            <template x-if="route === 'short'"><div class="contents"><span class="rounded-md bg-red-50 px-3 py-2 text-sm font-bold text-danger">⚠ Acera bloqueada</span><span class="rounded-md bg-red-50 px-3 py-2 text-sm font-bold text-danger">⚠ Desvío junto al tránsito</span><span class="rounded-md bg-red-50 px-3 py-2 text-sm font-bold text-danger">⚠ Cruce sin preparar</span></div></template>
            <template x-if="route === 'safe'"><div class="contents"><span class="rounded-md bg-emerald-50 px-3 py-2 text-sm font-bold text-success-text">✓ Acera continua</span><span class="rounded-md bg-emerald-50 px-3 py-2 text-sm font-bold text-success-text">✓ Rampa accesible</span><span class="rounded-md bg-emerald-50 px-3 py-2 text-sm font-bold text-success-text">✓ Paso visible</span></div></template>
        </div>
        <p x-show="error" x-cloak class="mt-3 text-sm font-bold text-danger" role="alert" x-text="error"></p>
        <p class="mt-4 text-xs text-text-secondary">Las líneas ayudan a comparar condiciones del recorrido; no representan distancias exactas. La persona debe comprobar el tránsito antes de cruzar incluso en el paso peatonal.</p>
    </div>
</section>
