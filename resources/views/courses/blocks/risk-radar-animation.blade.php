<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="riskRadar3d()">
    <header class="border-b border-border px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">Radar interactivo · 3D</p>
        <h4 class="mt-1 font-heading text-lg font-bold">Mirá más allá de lo evidente</h4>
        <p class="mt-1 text-sm text-text-secondary">Elegí una escena, buscá la pista y luego revelá el riesgo.</p>
    </header>

    <div x-ref="viewport" style="height:clamp(320px,48vw,500px);position:relative;background:#b9dbea">
        <div x-show="!ready" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 bg-primary/90 p-6 text-center text-white">
            <p class="max-w-lg text-sm" x-text="error || 'Abrí el radar 3D para investigar riesgos que no se distinguen a primera vista.'"></p>
            <button type="button" class="min-h-11 rounded-md bg-white px-5 font-bold text-primary" @click="open()" :disabled="loading" x-text="loading ? 'Preparando radar…' : 'Abrir radar 3D'"></button>
        </div>
        <div class="pointer-events-none absolute left-3 top-3 rounded-full bg-surface/95 px-3 py-2 text-xs font-bold text-text shadow-sm" x-text="scenes[scene].status"></div>
    </div>

    <div class="p-4">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" x-show="ready" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="togglePause()" x-text="paused ? 'Reanudar movimiento' : 'Pausar movimiento'"></button>
            <label class="flex items-center gap-2 text-sm">Cámara <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)"><option value="overview">Vista general</option><option value="walker">Desde la persona</option><option value="hazard">Desde el riesgo</option></select></label>
            <button type="button" x-show="revealed" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="replay()">Repetir revelación</button>
        </div>

        <p class="mt-3 font-bold text-text" x-text="scenes[scene].title"></p>
        <p class="mt-1 min-h-10 text-sm leading-6 text-text-secondary" x-text="scenes[scene].clue"></p>
        <div x-show="revealed" x-cloak class="mt-3 rounded-md border border-primary/25 bg-primary/5 p-3" aria-live="polite">
            <p class="text-xs font-bold uppercase tracking-wide text-primary">Riesgo revelado</p>
            <p class="mt-1 text-sm leading-6 text-text" x-text="scenes[scene].answer"></p>
        </div>
        <button type="button" class="mt-3 min-h-11 w-full rounded-md bg-primary px-4 py-2 text-sm font-bold text-white" @click="toggleReveal()" :aria-expanded="revealed"><span x-text="revealed ? 'Ocultar explicación' : 'Revelar el riesgo'"></span></button>
        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3" aria-label="Escenas del radar">
            <button type="button" class="min-h-11 rounded-md border px-2 text-sm font-bold" :class="scene === 'oculto' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="choose('oculto')">🚌 Punto ciego</button>
            <button type="button" class="min-h-11 rounded-md border px-2 text-sm font-bold" :class="scene === 'lluvia' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="choose('lluvia')">🌧️ Lluvia</button>
            <button type="button" class="min-h-11 rounded-md border px-2 text-sm font-bold" :class="scene === 'puerta' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="choose('puerta')">🚗 Puerta</button>
        </div>
    </div>
</section>
