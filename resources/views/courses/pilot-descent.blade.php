<x-layouts.app title="EDUDRIVE — Cambió el lugar de descenso">
    <div x-data="pilotVisual('descent')" class="mx-auto max-w-4xl space-y-4">
        <p class="text-sm">Prototipo de práctica · Sin calificación ni registro de respuestas</p>
        <h1 class="text-2xl font-bold">Cambió el lugar de descenso</h1>
        <p>El transporte se detuvo, pero la puerta quedó frente a un borde sin acera. Luna todavía está dentro con una persona adulta.</p>
        <div x-ref="scene" class="h-[420px] overflow-hidden rounded-lg border border-border" aria-label="Luna dentro de un transporte detenido"></div>
        <p x-show="error" x-text="error" role="status"></p>
        <noscript><p>Un microbús está detenido. La puerta abierta queda frente a un espaldón de grava sin acera. Luna y la persona adulta todavía están dentro. ¿Qué comunicaría Luna antes de bajar?</p></noscript>
        <div class="flex flex-wrap gap-3"><button @click="narrate" class="min-h-11 rounded border border-border px-4">Escuchar situación</button><button @click="stopVoice" class="min-h-11 rounded border border-border px-4">Detener voz</button></div>
        <details class="rounded border border-border p-3"><summary>Descripción sin imagen</summary><p>El microbús está completamente detenido en una vía con un carril por sentido. La puerta está abierta hacia un espaldón de grava, sin acera ni espacio peatonal protegido. Luna y una persona adulta aún permanecen dentro. La actividad termina antes de realizar cualquier maniobra del vehículo.</p></details>
        <section x-show="!choice" class="space-y-3">
            <h2 class="text-xl font-semibold">¿Qué comunicarías antes de bajar?</h2>
            <button @click="choose('wait')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Decirle al adulto que no hay un lugar protegido y permanecer dentro</button>
            <button @click="choose('go')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Bajar rápido porque el transporte ya se detuvo</button>
        </section>
        <section x-show="choice" x-cloak class="space-y-3" aria-live="polite">
            <p x-show="!result" x-text="choice === 'wait' ? 'Luna se acerca a su acompañante y comunica lo que observa.' : 'Luna empieza a bajar hacia el espaldón. Pausaremos la representación.'"></p>
            <div x-show="!result" class="flex flex-wrap gap-3"><button @click="pause" class="min-h-11 rounded border border-border px-4" x-text="paused ? 'Continuar animación' : 'Pausar animación'"></button><button @click="finish" class="min-h-11 rounded border border-border px-4">Ver desenlace sin movimiento</button></div>
            <div x-show="result" class="space-y-3 rounded-lg border border-border bg-surface p-4">
                <h2 class="text-xl font-semibold" x-text="choice === 'wait' ? 'Comunicar antes de exponerse' : 'Que el vehículo se detenga no vuelve seguro cualquier lugar'"></h2>
                <p x-show="choice === 'wait'">Luna permanece dentro y explica que no hay acera. El adulto y la persona conductora deben resolver un descenso protegido; Luna no tiene que improvisar una maniobra ni decidir dónde mover el vehículo.</p>
                <p x-show="choice !== 'wait'">Congelamos la escena cuando Luna empieza a salir. El espaldón no ofrece un espacio peatonal protegido. Bajar rápido no corrige el problema del lugar de descenso y no mostramos una caída ni un choque.</p>
                <p class="font-semibold">Contale a tu acompañante: ¿qué viste afuera que te hizo pedir ayuda?</p>
                <button @click="reset" class="min-h-11 rounded border border-border px-4">Probar otra decisión</button>
                <button x-show="!reduced && ready" @click="replay" class="min-h-11 rounded border border-border px-4">Repetir desenlace</button>
                <a class="inline-block min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" href="{{ route('pilot-instruments.visual-sequence') }}">Terminar y volver al recorrido</a>
            </div>
        </section>
        <a class="inline-block p-3 underline" href="{{ route('pilot-instruments.visual-sequence') }}">Volver al recorrido visual</a>
    </div>
</x-layouts.app>
