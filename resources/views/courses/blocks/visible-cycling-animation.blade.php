<section class="overflow-hidden rounded-lg border border-border bg-surface" x-data="{
    step: 0,
    steps: [
        { icon: '⛑️', title: 'Casco', text: 'Nivelado, ajustado y bien abrochado.' },
        { icon: '🛑', title: 'Frenos', text: 'Prueba ambos antes de iniciar el recorrido.' },
        { icon: '🛞', title: 'Llantas y piezas', text: 'Comprueba el aire y que nada esté flojo.' },
        { icon: '💡', title: 'Visibilidad', text: 'Adapta luces, reflectivos y color a las condiciones.' },
        { icon: '👀', title: 'Atención', text: 'Guarda pantallas y audífonos; conserva ojos, oídos y manos disponibles.' }
    ],
    next() { this.step = (this.step + 1) % this.steps.length },
    previous() { this.step = (this.step + this.steps.length - 1) % this.steps.length }
}" tabindex="0" @keydown.right.prevent="next()" @keydown.left.prevent="previous()">
    <div class="border-b border-border px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Revisión animada</p>
        <h4 class="mt-1 font-heading text-lg font-bold">Cinco puntos antes de pedalear</h4>
    </div>
    <div class="grid min-h-48 place-items-center bg-sky-100 p-5 text-center text-slate-900" role="img" :aria-label="steps[step].title + '. ' + steps[step].text">
        <div>
            <div class="text-7xl transition-transform duration-300 motion-reduce:transition-none" :class="step === 3 ? 'scale-110' : 'scale-100'" aria-hidden="true" x-text="steps[step].icon"></div>
            <div class="mt-3 text-5xl" aria-hidden="true">🚲</div>
        </div>
    </div>
    <div class="p-4">
        <div aria-live="polite">
            <p class="font-heading font-bold text-primary" x-text="`${step + 1}. ${steps[step].title}`"></p>
            <p class="mt-1 min-h-10 text-sm leading-5 text-text-secondary" x-text="steps[step].text"></p>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="button" class="min-h-[44px] rounded-md border border-border px-3 text-sm font-medium hover:bg-background" @click="previous()">← Anterior</button>
            <button type="button" class="min-h-[44px] rounded-md bg-primary px-3 text-sm font-medium text-white hover:bg-secondary" @click="next()">Siguiente →</button>
            <span class="ml-auto text-xs text-text-secondary" x-text="`${step + 1} de ${steps.length}`"></span>
        </div>
        <p class="mt-3 text-xs text-text-secondary">Podés recorrer la revisión con los botones o con las flechas del teclado.</p>
    </div>
</section>
