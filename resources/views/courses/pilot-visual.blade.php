<x-layouts.app title="EDUDRIVE — Una decisión antes de cruzar">
    <div x-data="pilotVisual" class="mx-auto max-w-4xl space-y-4">
        <p class="text-sm">Prototipo visual de práctica · Sin calificación ni registro de respuestas</p>
        <h1 class="text-2xl font-bold">Luna quiere llegar a la otra acera</h1>
        <p>Está acompañada y todavía no empezó a cruzar.</p>
        <div class="flex flex-wrap gap-3"><span class="rounded border border-border p-2">Señal peatonal: permite cruzar</span><span class="rounded border border-border p-2">Carro: comienza un giro a la derecha</span></div>
        <div x-ref="scene" class="h-80 overflow-hidden rounded-lg border border-border" aria-label="Escena del cruce"></div>
        <p x-show="error" x-text="error" role="status"></p>
        <noscript><p>La escena necesita JavaScript. Alternativa: Luna y un adulto están en la acera. Quieren cruzar por el paso de cebra; la señal peatonal está verde, pero un carro comienza a girar hacia ese paso. Pensá qué harías y explicalo a tu acompañante.</p></noscript>
        <div class="flex flex-wrap gap-3">
            <button type="button" @click="narrate" class="min-h-11 rounded border border-border px-4">Escuchar situación</button>
            <button type="button" @click="stopVoice" class="min-h-11 rounded border border-border px-4">Detener voz</button>
        </div>
        <details class="rounded border border-border p-3"><summary>Descripción sin imagen</summary><p>Luna y un adulto quieren cruzar hacia la acera de enfrente. Aún están en la acera. La señal peatonal permite avanzar. Un automóvil tiene la direccional derecha encendida y empieza a girar hacia el paso de cebra.</p></details>
        <section x-show="!choice" class="space-y-3">
            <h2 class="text-xl font-semibold">¿Qué harías ahora?</h2>
            <button type="button" @click="choose('go')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Empezar a cruzar porque la señal está verde</button>
            <button type="button" @click="choose('wait')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Esperar en la acera y comprobar el giro</button>
            <p class="text-sm">Primero elegí. Después veremos qué ocurre. No hay premio por responder rápido.</p>
        </section>
        <section x-show="choice" x-cloak class="space-y-3" aria-live="polite">
            <p x-show="!result" x-text="choice === 'wait' ? 'Luna espera mientras el carro gira y se detiene antes del paso.' : 'Luna y su acompañante empiezan a cruzar mientras el carro todavía está girando.'"></p>
            <div x-show="!result" class="flex flex-wrap gap-3"><button @click="pause" class="min-h-11 rounded border border-border px-4" x-text="paused ? 'Continuar animación' : 'Pausar animación'"></button><button @click="finish" class="min-h-11 rounded border border-border px-4">Ver desenlace sin movimiento</button></div>
            <div x-show="result" class="space-y-3 rounded-lg border border-border bg-surface p-4">
                <h2 class="text-xl font-semibold" x-text="choice === 'wait' ? 'Esperar permite comprobar' : 'El verde no elimina el conflicto'"></h2>
                <p x-show="choice === 'wait'">Luna espera. El carro se detiene y deja libre el paso. En este ejemplo, ella y su acompañante comprueban los demás movimientos antes de cruzar.</p>
                <p x-show="choice !== 'wait'">Luna y su acompañante comenzaron a cruzar sin confirmar el giro. Congelamos toda la escena antes de un posible encuentro con el carro: esto no significa que el vehículo ya se haya detenido para darles paso. La pausa es de la simulación, no una indicación de quedarse en medio de la calle. La decisión a revisar es haber salido de la acera sin comprobar el conflicto. No representamos un choque.</p>
                <p>Quien conduce debe respetar al peatón. Esta práctica no autoriza cruzar sin acompañamiento.</p>
                <p class="font-semibold">Contale a tu acompañante: ¿qué necesitabas comprobar además del color de la señal?</p>
                <button @click="reset" class="min-h-11 rounded border border-border px-4">Probar otra decisión</button>
                <button x-show="!reduced && ready" @click="replay" class="min-h-11 rounded border border-border px-4">Repetir desenlace</button>
                <a class="inline-block min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" href="{{ route('pilot-instruments.barrier') }}">Siguiente: cambiar el plan</a>
            </div>
        </section>
        <a href="{{ route('pilot-instruments.visual-sequence') }}" class="inline-block p-3 underline">Volver al recorrido visual</a>
    </div>
</x-layouts.app>
