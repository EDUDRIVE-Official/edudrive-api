<x-layouts.app title="EDUDRIVE — Recorrido visual">
    <div x-data="pilotSequence" class="mx-auto max-w-5xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm">Ensayo interno · Primaria de 9 a 12 años</p>
            <h1 class="text-3xl font-bold">Observo, compruebo y cambio mi plan</h1>
            <p class="max-w-3xl">Cuatro situaciones diferentes. En cada una elegís qué hacer y observás la consecuencia.</p>
            <div class="max-w-xl space-y-2" aria-live="polite">
                <div class="flex items-center justify-between"><span class="font-semibold">Avance en esta pestaña</span><span x-text="`${completed.length} de 4`"></span></div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Avance del recorrido" :aria-valuenow="completed.length" aria-valuemin="0" aria-valuemax="4"><div class="h-full bg-primary transition-all" :style="`width: ${(completed.length/4)*100}%`"></div></div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a x-show="!has('van')" x-cloak class="inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.van') }}">Comenzar el recorrido</a>
                <a x-show="has('van') && !has('turn')" x-cloak class="inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.visual') }}">Continuar con la práctica 2</a>
                <a x-show="has('van') && has('turn') && !has('barrier')" x-cloak class="inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.barrier') }}">Continuar con la práctica 3</a>
                <a x-show="has('van') && has('turn') && has('barrier') && !has('descent')" x-cloak class="inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.descent') }}">Continuar con la práctica 4</a>
            </div>
        </header>

        <ol class="grid gap-5 md:grid-cols-2" aria-label="Cuatro prácticas visuales en orden">
            <li class="rounded-xl border border-border bg-surface p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-full bg-sky-100 text-2xl" aria-hidden="true">👀</span><p class="font-semibold">1 · OBSERVO</p></div>
                <div class="flex items-start justify-between gap-3"><h2 class="text-xl font-bold">La van que bloquea la vista</h2><span class="rounded-full px-3 py-1 text-sm" :class="has('van') ? 'bg-green-100 text-green-900' : 'bg-slate-100 text-slate-700'" x-text="has('van') ? 'Completada' : 'Pendiente'"></span></div>
                <p class="mt-2">Buscá una mejor posición sin salir de la acera.</p>
                <a class="mt-4 inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.van') }}" x-text="has('van') ? 'Revisar' : 'Comenzar'"></a>
            </li>
            <li class="rounded-xl border border-border bg-surface p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-full bg-amber-100 text-2xl" aria-hidden="true">↪️</span><p class="font-semibold">2 · COMPRUEBO</p></div>
                <div class="flex items-start justify-between gap-3"><h2 class="text-xl font-bold">El carro que gira</h2><span class="rounded-full px-3 py-1 text-sm" :class="has('turn') ? 'bg-green-100 text-green-900' : 'bg-slate-100 text-slate-700'" x-text="has('turn') ? 'Completada' : 'Pendiente'"></span></div>
                <p class="mt-2">La señal permite cruzar, pero todavía hay un movimiento que comprobar.</p>
                <a class="mt-4 inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.visual') }}" x-text="has('turn') ? 'Revisar' : 'Abrir práctica'"></a>
            </li>
            <li class="rounded-xl border border-border bg-surface p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-full bg-orange-100 text-2xl" aria-hidden="true">🚧</span><p class="font-semibold">3 · CAMBIO EL PLAN</p></div>
                <div class="flex items-start justify-between gap-3"><h2 class="text-xl font-bold">Ruta interrumpida</h2><span class="rounded-full px-3 py-1 text-sm" :class="has('barrier') ? 'bg-green-100 text-green-900' : 'bg-slate-100 text-slate-700'" x-text="has('barrier') ? 'Completada' : 'Pendiente'"></span></div>
                <p class="mt-2">La acera termina. Decidí desde el espacio protegido.</p>
                <a class="mt-4 inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.barrier') }}" x-text="has('barrier') ? 'Revisar' : 'Abrir práctica'"></a>
            </li>
            <li class="rounded-xl border border-border bg-surface p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-full bg-violet-100 text-2xl" aria-hidden="true">🚌</span><p class="font-semibold">4 · COMUNICO</p></div>
                <div class="flex items-start justify-between gap-3"><h2 class="text-xl font-bold">Cambió el lugar de descenso</h2><span class="rounded-full px-3 py-1 text-sm" :class="has('descent') ? 'bg-green-100 text-green-900' : 'bg-slate-100 text-slate-700'" x-text="has('descent') ? 'Completada' : 'Pendiente'"></span></div>
                <p class="mt-2">Detectá el problema antes de bajar y pedí apoyo.</p>
                <a class="mt-4 inline-block min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" href="{{ route('pilot-instruments.descent') }}" x-text="has('descent') ? 'Revisar' : 'Abrir práctica'"></a>
            </li>
        </ol>

        <section x-show="completed.length === 4" x-cloak class="space-y-3 rounded-xl border-2 border-green-500 bg-green-50 p-5 text-green-950" aria-live="polite">
            <p class="font-semibold">RECORRIDO COMPLETADO</p>
            <h2 class="text-2xl font-bold">Terminaste las cuatro prácticas</h2>
            <p>Ahora podés conversar con una persona adulta sobre estas cuatro ideas:</p>
            <ul class="grid gap-2 md:grid-cols-2">
                <li class="rounded bg-white/70 p-3">Observar desde un espacio protegido.</li>
                <li class="rounded bg-white/70 p-3">Comprobar movimientos aunque la señal permita cruzar.</li>
                <li class="rounded bg-white/70 p-3">Cambiar el plan cuando la ruta deja de ser segura.</li>
                <li class="rounded bg-white/70 p-3">Comunicar un problema antes de bajar.</li>
            </ul>
            <p class="text-sm">Completar este ensayo no es una calificación ni demuestra dominio por sí solo.</p>
        </section>

        <aside class="rounded-lg border border-border p-4" aria-label="Alcance del ensayo">
            <p><strong>Importante:</strong> estas prácticas no califican ni guardan respuestas. Las marcas de avance son temporales y solo existen en esta pestaña.</p>
        </aside>
        <button x-show="completed.length" @click="resetProgress" class="min-h-11 rounded border border-border px-4">Reiniciar marcas de avance</button>
        <a class="inline-block min-h-11 rounded border border-border px-4 py-3 font-semibold" href="{{ route('pilot-instruments.visual-review') }}">Abrir revisión interna de calidad</a>
        <a class="inline-block p-3 underline" href="{{ route('pilot-instruments.unit') }}">Volver a la guía docente</a>
    </div>
</x-layouts.app>
