<section
    class="overflow-hidden rounded-lg border border-border bg-surface"
    x-data="{
        state: 'telefono',
        step: 0,
        states: {
            telefono: { icon: '📱', title: 'Distracción', signal: 'El teléfono vibra antes del cruce.', action: 'Guardá el teléfono y levantá la mirada.' },
            prisa: { icon: '⏰', title: 'Prisa', signal: 'Sentís que debés avanzar ya para no llegar tarde.', action: 'Aceptá la demora y elegí la opción con margen.' },
            cansancio: { icon: '🥱', title: 'Cansancio', signal: 'Perdés detalles y te cuesta mantener la atención.', action: 'Detenete en un lugar seguro, descansá o pedí apoyo.' },
            presion: { icon: '👥', title: 'Presión del grupo', signal: 'Otras personas te llaman para cruzar con ellas.', action: 'Mantené tu límite: “Yo espero; nos vemos al otro lado”.' },
        },
        choose(next) { this.state = next; this.step = 0 },
        advance() { if (this.step < 3) this.step++ },
        reset() { this.step = 0 },
    }"
>
    <header class="border-b border-border px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-primary">Laboratorio de autocuidado</p>
        <h4 class="mt-1 font-heading text-lg font-bold">Pausa antes de actuar</h4>
        <p class="mt-1 text-sm leading-6 text-text-secondary">Elegí una dificultad y recorré los pasos para recuperar el control.</p>
    </header>

    <div class="grid lg:grid-cols-[minmax(0,.75fr)_minmax(0,1.25fr)]">
        <div class="border-b border-border bg-background p-4 lg:border-b-0 lg:border-r">
            <p class="text-xs font-bold uppercase tracking-wide text-text-secondary">¿Qué compite con tu atención?</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <template x-for="(item, key) in states" :key="key">
                    <button
                        type="button"
                        class="min-h-20 rounded-lg border p-3 text-left transition-colors"
                        :class="state === key ? 'border-primary bg-primary/10 text-primary' : 'border-border bg-surface text-text'"
                        @click="choose(key)"
                        :aria-pressed="(state === key).toString()"
                    >
                        <span class="text-2xl" aria-hidden="true" x-text="item.icon"></span>
                        <span class="mt-1 block text-sm font-bold" x-text="item.title"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-2xl" aria-hidden="true" x-text="states[state].icon"></span>
                <div><p class="text-xs font-bold uppercase tracking-wide text-primary">Señal detectada</p><p class="font-bold text-text" x-text="states[state].title"></p></div>
            </div>
            <p class="mt-3 min-h-12 text-sm leading-6 text-text-secondary" x-text="states[state].signal"></p>

            <ol class="mt-4 grid grid-cols-4 gap-2" aria-label="Secuencia para recuperar la atención">
                <li class="rounded-md border p-2 text-center text-xs" :class="step >= 0 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-secondary'"><span class="block text-lg" aria-hidden="true">✋</span><strong>Reconocé</strong></li>
                <li class="rounded-md border p-2 text-center text-xs" :class="step >= 1 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-secondary'"><span class="block text-lg" aria-hidden="true">🛑</span><strong>Detenete</strong></li>
                <li class="rounded-md border p-2 text-center text-xs" :class="step >= 2 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-secondary'"><span class="block text-lg" aria-hidden="true">🌬️</span><strong>Regulá</strong></li>
                <li class="rounded-md border p-2 text-center text-xs" :class="step >= 3 ? 'border-success bg-success/10 text-success-text' : 'border-border text-text-secondary'"><span class="block text-lg" aria-hidden="true">🧭</span><strong>Replanteá</strong></li>
            </ol>

            <div class="mt-4 min-h-24 rounded-lg border p-3" :class="step === 3 ? 'border-success/30 bg-success/10' : 'border-border bg-background'" aria-live="polite">
                <template x-if="step === 0"><p class="text-sm leading-6"><strong>1. Reconocé:</strong> nombrá lo que está afectando tu atención. No lo minimicés.</p></template>
                <template x-if="step === 1"><p class="text-sm leading-6"><strong>2. Detenete:</strong> permanecé o volvé a un espacio protegido antes de resolverlo.</p></template>
                <template x-if="step === 2"><p class="text-sm leading-6"><strong>3. Regulá:</strong> respirá, retiralo o pedí apoyo. Comprobá si recuperaste la atención.</p></template>
                <template x-if="step === 3"><div><p class="text-sm font-bold text-success-text">4. Tu nuevo plan</p><p class="mt-1 text-sm leading-6 text-text" x-text="states[state].action"></p></div></template>
            </div>

            <div class="mt-3 flex gap-2">
                <button type="button" class="min-h-11 flex-1 rounded-md bg-primary px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50" @click="advance()" :disabled="step === 3">
                    <span x-text="step === 3 ? 'Pausa completada' : 'Siguiente paso →'"></span>
                </button>
                <button type="button" class="min-h-11 rounded-md border border-border px-4 py-2 text-sm font-bold text-text" @click="reset()">Repetir</button>
            </div>
        </div>
    </div>
</section>
