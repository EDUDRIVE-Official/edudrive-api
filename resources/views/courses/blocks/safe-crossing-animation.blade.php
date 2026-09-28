<section
    class="overflow-hidden rounded-lg border border-border bg-surface"
    x-data="{
        step: 0,
        playing: false,
        timer: null,
        steps: [
            { title: '1. Elige', text: 'Busca una esquina o paso peatonal donde puedas ver y ser visible.' },
            { title: '2. Detente', text: 'Espera sobre la acera, antes de entrar a la calzada.' },
            { title: '3. Observa y escucha', text: 'Comprueba todos los lugares desde donde podría aparecer un vehículo.' },
            { title: '4. Confirma', text: 'Asegúrate de que los vehículos se detuvieron y el cruce está libre.' },
            { title: '5. Cruza con calma', text: 'Avanza directo, atento y sin correr ni usar pantallas.' }
        ],
        next() { this.step = (this.step + 1) % this.steps.length },
        previous() { this.step = (this.step + this.steps.length - 1) % this.steps.length },
        toggle() {
            if (this.playing) {
                clearInterval(this.timer); this.timer = null; this.playing = false; return;
            }
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.playing = true;
            this.timer = setInterval(() => this.next(), 1800);
        },
        destroy() { if (this.timer) clearInterval(this.timer) }
    }"
    tabindex="0"
    @keydown.right.prevent="next()"
    @keydown.left.prevent="previous()"
>
    <div class="border-b border-border px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Animación guiada</p>
        <h4 class="mt-1 font-heading text-lg font-bold">La secuencia de cruce seguro</h4>
    </div>

    <div class="relative h-48 overflow-hidden bg-sky-100" role="img" :aria-label="steps[step].title + '. ' + steps[step].text">
        <div class="absolute inset-x-0 bottom-0 h-8 bg-stone-300"></div>
        <div class="absolute inset-x-0 bottom-8 h-24 bg-slate-600">
            <div class="absolute inset-x-0 top-1/2 border-t-4 border-dashed border-white/80"></div>
            <div class="absolute bottom-0 left-1/2 grid h-full w-24 -translate-x-1/2 grid-cols-4 gap-2 bg-slate-500 px-2">
                <span class="bg-white"></span><span class="bg-white"></span><span class="bg-white"></span><span class="bg-white"></span>
            </div>
        </div>
        <div class="absolute bottom-32 left-4 text-4xl" aria-hidden="true">🏠</div>
        <div class="absolute bottom-8 text-4xl transition-all duration-700 motion-reduce:transition-none" :style="`left: ${step < 4 ? 15 + (step * 11) : 72}%`" aria-hidden="true">🚶</div>
        <div x-show="step === 2" class="absolute left-1/2 top-4 -translate-x-1/2 rounded-full bg-white px-4 py-2 text-xl shadow" aria-hidden="true">👀 👂</div>
        <div x-show="step === 3" x-cloak class="absolute right-5 top-14 rounded-md bg-success px-3 py-2 text-sm font-bold text-white shadow" aria-hidden="true">Vehículos detenidos ✓</div>
    </div>

    <div class="p-4">
        <div aria-live="polite">
            <p class="font-heading font-bold text-primary" x-text="steps[step].title"></p>
            <p class="mt-1 min-h-10 text-sm leading-5 text-text-secondary" x-text="steps[step].text"></p>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="button" class="min-h-[44px] rounded-md border border-border px-3 text-sm font-medium hover:bg-background" @click="previous()" aria-label="Paso anterior">← Anterior</button>
            <button type="button" class="min-h-[44px] rounded-md border border-border px-3 text-sm font-medium hover:bg-background" @click="next()">Siguiente →</button>
            <button type="button" class="min-h-[44px] rounded-md bg-primary px-3 text-sm font-medium text-white hover:bg-secondary" @click="toggle()" :aria-label="playing ? 'Pausar animación' : 'Reproducir animación'">
                <span x-text="playing ? 'Pausar' : 'Reproducir'"></span>
            </button>
            <span class="ml-auto text-xs text-text-secondary" x-text="`${step + 1} de ${steps.length}`"></span>
        </div>
        <p class="mt-3 text-xs text-text-secondary">También podés avanzar con las flechas izquierda y derecha del teclado. La reproducción automática se desactiva si tu dispositivo prefiere movimiento reducido.</p>
    </div>
</section>
