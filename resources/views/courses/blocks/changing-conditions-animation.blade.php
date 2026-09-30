<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="changingConditions3d()">
    <header class="border-b border-border px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">Laboratorio visual · 3D</p>
        <h4 class="mt-1 font-heading text-lg font-bold">El mismo cruce, condiciones diferentes</h4>
    </header>
    <div x-ref="viewport" style="height:clamp(330px,50vw,520px);position:relative;background:#c9e6f3">
        <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center" style="color:#12324a">
            <p class="max-w-lg text-sm" x-text="error || 'Abrí el laboratorio 3D para comparar cómo cambia la visibilidad del mismo cruce con sol, lluvia y oscuridad.'"></p>
            <button type="button" class="min-h-11 rounded-md bg-primary px-5 font-bold text-white" @click="open()" :disabled="loading" x-text="loading ? 'Preparando laboratorio…' : 'Abrir laboratorio 3D'"></button>
        </div>
    </div>
    <div class="border-t border-border p-4">
        <p class="min-h-10 text-sm leading-6 text-text-secondary" aria-live="polite" x-text="messages[condition]"></p>
        <div class="mt-3 grid gap-2 sm:grid-cols-3">
            <button type="button" class="min-h-11 rounded-md border text-sm font-bold" :class="condition === 'clear' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="choose('clear')">☀️ Claro</button>
            <button type="button" class="min-h-11 rounded-md border text-sm font-bold" :class="condition === 'rain' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="choose('rain')">🌧️ Lluvia</button>
            <button type="button" class="min-h-11 rounded-md border text-sm font-bold" :class="condition === 'night' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="choose('night')">🌙 Noche</button>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="button" x-show="ready" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="togglePause()" x-text="paused ? 'Reanudar tránsito' : 'Pausar tránsito'"></button>
            <label class="flex items-center gap-2 text-sm">Cámara<select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)"><option value="overview">Vista general</option><option value="pedestrian">Desde la acera</option></select></label>
        </div>
        <details class="mt-4 text-sm text-text-secondary"><summary class="cursor-pointer font-medium">Alternativa accesible</summary><p class="mt-2 leading-6" x-text="descriptions[condition]"></p></details>
    </div>
</section>
