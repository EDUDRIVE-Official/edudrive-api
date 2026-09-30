<x-layouts.app title="EDUDRIVE — Ruta interrumpida">
    <div x-data="pilotVisual('barrier')" class="mx-auto max-w-4xl space-y-4">
        <p class="text-sm">Prototipo de práctica · Sin calificación ni registro de respuestas</p>
        <h1 class="text-2xl font-bold">Ruta interrumpida</h1>
        <p>Una obra bloquea toda la acera. Luna y una persona adulta siguen en el espacio peatonal y no ven una alternativa protegida para continuar.</p>
        <div x-ref="scene" class="h-[420px] overflow-hidden rounded-lg border border-border" aria-label="Luna ante una obra que bloquea la acera"></div>
        <p x-show="error" x-text="error" role="status"></p>
        <noscript><p>Una obra vallada ocupa todo el ancho de la acera. Luna y la persona adulta permanecen antes de la barrera. La calzada tiene un carril por sentido. ¿Qué harías?</p></noscript>
        <div class="flex flex-wrap gap-3"><button @click="narrate" class="min-h-11 rounded border border-border px-4">Escuchar situación</button><button @click="stopVoice" class="min-h-11 rounded border border-border px-4">Detener voz</button></div>
        <details class="rounded border border-border p-3"><summary>Descripción sin imagen</summary><p>La valla de una obra ocupa la acera desde el borde de la calle hasta el límite interior. Luna y su acompañante están antes de la barrera, dentro del espacio peatonal. No se muestra una ruta protegida para continuar. Que la calzada parezca vacía no significa que sea una alternativa segura.</p></details>
        <section x-show="!choice" class="space-y-3">
            <h2 class="text-xl font-semibold">¿Qué harías al encontrar la acera cerrada?</h2>
            <button @click="choose('wait')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Detenerme con el adulto y buscar juntos otra ruta protegida</button>
            <button @click="choose('go')" class="block min-h-11 w-full rounded border border-border p-4 text-left">Bajar a la calzada para rodear la obra</button>
        </section>
        <section x-show="choice" x-cloak class="space-y-3" aria-live="polite">
            <p x-show="!result" x-text="choice === 'wait' ? 'Luna y su acompañante retroceden dentro de la acera para detenerse y cambiar el plan.' : 'Luna empieza a acercarse a la calzada. Pausaremos la representación.'"></p>
            <div x-show="!result" class="flex flex-wrap gap-3"><button @click="pause" class="min-h-11 rounded border border-border px-4" x-text="paused ? 'Continuar animación' : 'Pausar animación'"></button><button @click="finish" class="min-h-11 rounded border border-border px-4">Ver desenlace sin movimiento</button></div>
            <div x-show="result" class="space-y-3 rounded-lg border border-border bg-surface p-4">
                <h2 class="text-xl font-semibold" x-text="choice === 'wait' ? 'Cambiar el plan desde un espacio protegido' : 'La calzada no reemplaza la acera'"></h2>
                <p x-show="choice === 'wait'">Ambos permanecen en la acera y se alejan de la barrera. Pueden pedir apoyo y buscar una ruta protegida; esta escena no inventa que una dirección desconocida sea segura.</p>
                <p x-show="choice !== 'wait'">Congelamos la escena antes de que Luna entre al carril. Rodear la obra por la calzada la expone al tránsito, aunque en ese instante no se vea un vehículo. No mostramos un choque.</p>
                <p class="font-semibold">Contale a tu acompañante: ¿qué necesitás averiguar antes de elegir otra ruta?</p>
                <button @click="reset" class="min-h-11 rounded border border-border px-4">Probar otra decisión</button>
                <button x-show="!reduced && ready" @click="replay" class="min-h-11 rounded border border-border px-4">Repetir desenlace</button>
                <a class="inline-block min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" href="{{ route('pilot-instruments.descent') }}">Siguiente: comunicar antes de bajar</a>
            </div>
        </section>
        <a class="inline-block p-3 underline" href="{{ route('pilot-instruments.visual-sequence') }}">Volver al recorrido visual</a>
    </div>
</x-layouts.app>
