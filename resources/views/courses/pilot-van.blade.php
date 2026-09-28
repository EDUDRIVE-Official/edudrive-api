<x-layouts.app title="EDUDRIVE — La van que bloquea la vista">
    <div x-data="pilotVisual('van')" class="mx-auto max-w-4xl space-y-4">
        <p class="text-sm">Prototipo de práctica · Sin calificación ni registro de respuestas</p>
        <h1 class="text-2xl font-bold">La van que bloquea la vista</h1>
        <p>Luna quiere llegar a la acera de enfrente con una persona adulta. La van tapa parte de la vía.</p>
        <div x-ref="scene" class="h-[420px] overflow-hidden rounded-lg border border-border" aria-label="Luna junto a la van"></div>
        <p x-show="error" x-text="error" role="status"></p>
        <noscript><p>Luna está en la acera. Una van estacionada oculta parte de la calle. A la derecha hay espacio libre dentro de la acera. ¿Dónde te ubicarías para observar antes de decidir cómo cruzar?</p></noscript>
        <div class="flex flex-wrap gap-3"><button @click="narrate" class="min-h-11 rounded border border-border px-4">Escuchar situación</button><button @click="stopVoice" class="min-h-11 rounded border border-border px-4">Detener voz</button></div>
        <details class="rounded border border-border p-3"><summary>Descripción sin imagen</summary><p>La calle tiene un carril por sentido. Luna y un adulto están en la acera, junto a la parte delantera de una van estacionada. El vehículo les tapa parte de la vista hacia la izquierda. A la derecha hay espacio libre para desplazarse por la acera. Quieren llegar enfrente, pero aún no han decidido cómo cruzar.</p></details>
        <section x-show="!choice" class="space-y-3">
            <h2 class="text-xl font-semibold">¿Dónde te ubicarías para observar?</h2>
            <button @click="choose('wait')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Moverme con el adulto por la acera, lejos de la van</button>
            <button @click="choose('go')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Dar un paso a la calle para asomarme</button>
        </section>
        <section x-show="choice" x-cloak class="space-y-3" aria-live="polite">
            <p x-show="!result" x-text="choice === 'wait' ? 'Luna y su acompañante cambian de posición dentro de la acera.' : 'Luna se acerca al borde y empieza a salir a la calle. Pausaremos la representación.'"></p>
            <div x-show="!result" class="flex flex-wrap gap-3"><button @click="pause" class="min-h-11 rounded border border-border px-4" x-text="paused ? 'Continuar animación' : 'Pausar animación'"></button><button @click="finish" class="min-h-11 rounded border border-border px-4">Ver desenlace sin movimiento</button></div>
            <div x-show="result" class="space-y-3 rounded-lg border border-border bg-surface p-4">
                <h2 class="text-xl font-semibold" x-text="choice === 'wait' ? 'Cambiar de posición sin exponerse' : 'Ver mejor no justifica salir a la calle'"></h2>
                <p x-show="choice === 'wait'">Ambos permanecen en la acera. Alejarse de la van puede mejorar la vista, pero no es permiso para cruzar desde ese punto. Todavía necesitan comprobar ambos sentidos y buscar con el adulto un lugar adecuado para cruzar.</p>
                <p x-show="choice !== 'wait'">Congelamos la escena cuando Luna empieza a entrar en la calzada. Salir para ver la expone antes de conocer el tránsito que la van oculta. La alternativa es buscar otra posición dentro de la acera con su acompañante. No mostramos un choque.</p>
                <p class="font-semibold">Contale a tu acompañante: ¿qué información seguís necesitando antes de cruzar?</p>
                <button @click="reset" class="min-h-11 rounded border border-border px-4">Probar otra decisión</button>
                <button x-show="!reduced && ready" @click="replay" class="min-h-11 rounded border border-border px-4">Repetir desenlace</button>
                <a class="inline-block min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" href="{{ route('pilot-instruments.visual') }}">Siguiente: comprobar un giro</a>
            </div>
        </section>
        <a class="inline-block p-3 underline" href="{{ route('pilot-instruments.visual-sequence') }}">Volver al recorrido visual</a>
    </div>
</x-layouts.app>
