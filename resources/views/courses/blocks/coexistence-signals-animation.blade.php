<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="coexistenceSignals3d()">
    <header class="border-b border-border px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">Laboratorio de convivencia · 3D</p>
        <h4 class="mt-1 font-heading text-lg font-bold">La señal es una pista, no toda la historia</h4>
        <p class="mt-1 text-sm leading-6 text-text-secondary">Cambiá la situación y comprobá si la misma señal conduce a la misma decisión.</p>
    </header>
    <div class="grid gap-0 lg:grid-cols-[minmax(0,1.25fr)_minmax(18rem,.75fr)]">
        <div class="relative min-h-80 overflow-hidden bg-sky-100" x-ref="viewport">
            <div x-show="!ready" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 bg-gradient-to-b from-sky-100 to-emerald-50 p-6 text-center">
                <p class="max-w-lg text-sm text-slate-700" x-text="error || 'Abrí el cruce 3D y compará cómo cambia la decisión cuando aparece información nueva.'"></p>
                <button type="button" class="min-h-11 rounded-md bg-primary px-5 font-bold text-white" @click="open()" :disabled="loading" x-text="loading ? 'Preparando laboratorio…' : 'Abrir laboratorio 3D'"></button>
            </div>
            <div x-show="ready" x-cloak class="pointer-events-none absolute bottom-3 right-3 rounded-md bg-slate-900/80 px-3 py-2 text-xs text-white" x-text="view === 'overview' ? 'Arrastrá para observar · rueda para acercar' : 'La cámara acompaña lo que observa Luna'"></div>
        </div>
        <div class="p-4">
            <p class="min-h-16 text-sm font-bold leading-6 text-text" x-text="scenes[scene].question"></p>
            <div class="mt-3 grid gap-2">
                <button type="button" class="min-h-11 rounded-md border px-3 py-2 text-left text-sm" :class="answer === 'safe' ? 'border-success bg-success/10 text-success-text' : 'border-border'" @click="decide('safe')" x-text="scenes[scene].safe"></button>
                <button type="button" class="min-h-11 rounded-md border px-3 py-2 text-left text-sm" :class="answer === 'unsafe' ? 'border-danger bg-danger/10 text-danger-text' : 'border-border'" @click="decide('unsafe')" x-text="scenes[scene].unsafe"></button>
            </div>
            <div x-show="answer" x-cloak class="mt-3 rounded-md p-3 text-sm leading-6" :class="answer === 'safe' ? 'bg-success/10 text-success-text' : 'bg-danger/10 text-danger-text'" aria-live="polite">
                <p class="font-bold" x-text="answer === 'safe' ? 'Decisión segura' : 'Revisá tu decisión'"></p>
                <p class="mt-1" x-text="answer === 'safe' ? scenes[scene].feedback : 'Una regla o la conducta de otra persona no elimina el peligro. Buscá una opción con más margen.'"></p>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 border-t border-border px-4 py-3">
        <button type="button" x-show="ready" x-cloak class="min-h-11 rounded-md border border-border px-3 text-sm" @click="togglePause()" x-text="paused ? 'Reanudar movimiento' : 'Pausar movimiento'"></button>
        <label x-show="ready" x-cloak class="flex items-center gap-2 text-sm">Cámara <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)"><option value="overview">Vista general</option><option value="pedestrian">Desde Luna</option></select></label>
        <button type="button" x-show="ready && answer" x-cloak class="min-h-11 rounded-md border border-border px-3 text-sm" @click="replay()">Repetir resultado</button>
    </div>
    <div class="grid grid-cols-3 gap-2 border-t border-border p-4" aria-label="Situaciones disponibles">
        <button type="button" class="min-h-11 rounded-md border px-2 text-xs font-bold sm:text-sm" :class="scene === 'normal' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="chooseScene('normal')">✓ Todo coincide</button>
        <button type="button" class="min-h-11 rounded-md border px-2 text-xs font-bold sm:text-sm" :class="scene === 'conflict' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="chooseScene('conflict')">⚠ Hay conflicto</button>
        <button type="button" class="min-h-11 rounded-md border px-2 text-xs font-bold sm:text-sm" :class="scene === 'failure' ? 'border-primary bg-primary/10 text-primary' : 'border-border'" @click="chooseScene('failure')">● Señal apagada</button>
    </div>
</section>
