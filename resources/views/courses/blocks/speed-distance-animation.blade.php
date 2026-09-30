<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="speedDistance3d()">
    <header class="border-b border-border px-4 py-3"><p class="text-xs font-bold uppercase tracking-wide text-primary">Microdemostración · 3D</p><h4 class="mt-1 font-heading text-lg font-bold">La misma distancia, distinto tiempo</h4></header>
    <div x-ref="viewport" style="height:clamp(320px,48vw,500px);position:relative;background:#b9dbea">
        <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center" style="color:#12324a">
            <p class="max-w-lg text-sm" x-text="error || 'Abrí la demostración 3D para comparar cómo cambia el tiempo disponible cuando un vehículo recorre exactamente la misma distancia.'"></p>
            <button type="button" class="min-h-11 rounded-md bg-primary px-5 font-bold text-white" @click="open()" :disabled="loading" x-text="loading ? 'Preparando demostración…' : 'Abrir demostración 3D'"></button>
        </div>
    </div>
    <div class="p-4">
        <p class="text-sm text-text-secondary" aria-live="polite" x-text="reduced ? 'Tu dispositivo tiene activada la reducción de movimiento. Podés leer la comparación sin reproducirla.' : message"></p>
        <div class="mt-3 grid gap-2 sm:grid-cols-3" aria-label="Etapas conceptuales">
            <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm"><strong>1. Percibir</strong><br>Detectar el movimiento.</div>
            <div class="rounded-md border border-orange-300 bg-orange-50 p-3 text-sm"><strong>2. Decidir</strong><br>Interpretar el riesgo.</div>
            <div class="rounded-md border border-red-300 bg-red-50 p-3 text-sm"><strong>3. Protegerse</strong><br>Continuar en la acera.</div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm font-bold disabled:opacity-50" @click="run('slow')" :disabled="reduced">Probar lento</button>
            <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm font-bold disabled:opacity-50" @click="run('fast')" :disabled="reduced">Probar rápido</button>
            <button type="button" x-show="ready" class="min-h-11 rounded-md border border-border px-3 text-sm font-bold" @click="togglePause()" x-text="paused ? 'Reanudar' : 'Pausar'"></button>
            <button type="button" class="min-h-11 rounded-md bg-primary px-3 text-sm font-bold text-white" @click="reset()">Detener y reiniciar</button>
            <label class="flex items-center gap-2 text-sm">Cámara <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)"><option value="overview">Vista general</option><option value="pedestrian">Desde Luna</option><option value="driver">Desde el vehículo</option></select></label>
        </div>
        <p class="mt-3 text-xs text-text-secondary">Las marcas amarilla, naranja y roja representan el tiempo para percibir, decidir y conservarse protegido. La demostración es conceptual: no calcula una distancia segura para cruzar.</p>
    </div>
</section>
