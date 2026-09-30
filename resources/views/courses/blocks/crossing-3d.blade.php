<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="crossing3d('{{ $sceneMode ?? 'crossing' }}')" @keydown.right.prevent="next()" @keydown.left.prevent="previous()">
    <header class="border-b border-border p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">{{ $sceneLabel ?? 'Misión Camino Seguro · Prueba 3D' }}</p>
        <h4 class="mt-1 font-heading text-lg font-bold">{{ $sceneTitle ?? 'Antes de cruzar, entendé lo que sucede' }}</h4>
        <p class="mt-1 text-sm text-text-secondary">{{ $sceneDescription ?? 'Observá el tránsito en ambos sentidos y acompañá el cruce paso a paso.' }}</p>
    </header>
    <div x-ref="viewport" style="height:clamp(300px,48vw,500px);position:relative;background:#c6deed">
        <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center" style="color:#12324a">
            <p class="max-w-md text-sm" x-text="error || 'Explorá una calle en tres dimensiones, con vehículos, aceras y paso peatonal.'"></p>
            <button type="button" class="min-h-11 rounded-md bg-primary px-5 py-2 font-bold text-white" @click="open()" :disabled="loading" x-text="loading ? 'Preparando escena…' : 'Abrir escena 3D'"></button>
        </div>
    </div>
    <div class="space-y-3 p-4">
        <div aria-live="polite"><p class="font-bold text-primary" x-text="steps[step][0]"></p><p class="mt-1 text-sm text-text-secondary" x-text="steps[step][1]"></p></div>
        <div class="flex flex-wrap gap-2">
            <button type="button" x-show="ready" class="min-h-11 rounded-md border border-border px-3" @click="togglePause()" :aria-pressed="paused.toString()" x-text="paused ? 'Reanudar movimiento' : 'Pausar movimiento'"></button>
            <button type="button" class="min-h-11 rounded-md border border-border px-3" @click="previous()" :disabled="step === 0">Anterior</button>
            <button type="button" class="min-h-11 rounded-md bg-primary px-4 font-bold text-white" @click="next()" :disabled="step === steps.length - 1">Siguiente paso</button>
            <button type="button" class="min-h-11 rounded-md border border-border px-3" @click="step = 0; sync()">Reiniciar</button>
            @if (($sceneMode ?? '') === 'actors')
                <label class="flex items-center gap-2 text-sm">Cámara
                    <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)">
                        <option value="overview">Vista general</option><option value="pedestrian">Desde la acera</option><option value="driver">Puesto del conductor</option>
                    </select>
                </label>
            @else
                <button type="button" class="min-h-11 rounded-md border border-border px-3" @click="changeView(view === 'overview' ? 'pedestrian' : 'overview')" x-text="view === 'overview' ? 'Vista desde la acera' : 'Vista general'"></button>
            @endif
            <span class="self-center text-xs text-text-secondary" x-text="`${step + 1} de ${steps.length}`"></span>
            <button type="button" x-show="ready" class="min-h-11 rounded-md border border-border px-3" @click="close()">Cerrar escena</button>
        </div>
        <p class="text-xs text-text-secondary">Escena ilustrativa guiada. Un paso peatonal por sí solo no garantiza que los vehículos se detengan. Confirmá siempre que podés cruzar con seguridad. Los niños pequeños deben ir acompañados.</p>
    </div>
</section>
