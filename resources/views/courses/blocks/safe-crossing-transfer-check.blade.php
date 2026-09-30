@php $transferCheckRecorded = $transferCheckRecorded ?? false; @endphp
<section
    id="transfer-check"
    class="rounded-xl border-2 border-success/40 bg-surface p-5 shadow-sm"
    aria-labelledby="transfer-check-title"
    x-data="{
        current: 0,
        complete: false,
        answers: {},
        situations: [
            { id: 'visibility', skill: 'Percepción', text: 'Llueve al salir de clases. Un autobús detenido oculta un carril y el paso marcado queda unos metros adelante.', options: [
                { id: 'behind', label: 'Cruzar detrás del autobús para evitar mojarte más', safe: false },
                { id: 'marked', label: 'Ir al paso, esperar lejos del borde y recuperar visión de todos los carriles', safe: true },
                { id: 'group', label: 'Seguir al grupo si varias personas cruzan juntas', safe: false }
            ]},
            { id: 'priority', skill: 'Decisión', text: 'La señal peatonal permite avanzar, pero un vehículo gira y no reduce claramente la velocidad.', options: [
                { id: 'claim', label: 'Avanzar para hacer valer la prioridad', safe: false },
                { id: 'verify', label: 'Esperar, confirmar la trayectoria y avanzar solo con margen', safe: true },
                { id: 'wave', label: 'Hacer una señal y cruzar de inmediato', safe: false }
            ]},
            { id: 'inclusion', skill: 'Convivencia', text: 'Una obra bloquea la rampa y la alternativa inmediata obliga a una persona con movilidad reducida a entrar en la calzada.', options: [
                { id: 'quick', label: 'Ayudarla a pasar rápidamente por la calzada', safe: false },
                { id: 'route', label: 'Preguntar qué apoyo necesita y buscar una ruta accesible protegida', safe: true },
                { id: 'alone', label: 'Continuar porque cada persona decide su propia ruta', safe: false }
            ]},
            { id: 'selfcare', skill: 'Autocuidado', text: 'Vas tarde, recibís un mensaje urgente y tus amistades te llaman desde el otro lado de la vía.', options: [
                { id: 'rush', label: 'Cruzar rápido y responder después', safe: false },
                { id: 'pause', label: 'Alejarte del borde, resolver una demanda a la vez y reconstruir el cruce', safe: true },
                { id: 'read', label: 'Leer mientras esperás la señal para ahorrar tiempo', safe: false }
            ]}
        ],
        choose(situation, option) { this.answers[situation.id] = { ...option, skill: situation.skill }; },
        get answered() { return Object.keys(this.answers).length; },
        get score() { return Object.values(this.answers).filter(answer => answer.safe).length; },
        get strengths() { return Object.values(this.answers).filter(answer => answer.safe).map(answer => answer.skill); },
        get practice() { return Object.values(this.answers).filter(answer => ! answer.safe).map(answer => answer.skill); },
        get message() {
            if (this.score === 4) return 'Integraste los cuatro dominios y mantuviste margen aunque la situación cambiara.';
            if (this.score >= 2) return 'Tu criterio tiene buenas bases. Usá las áreas señaladas para elegir qué misión repasar.';
            return 'Todavía estás construyendo la secuencia. Repasar es parte del aprendizaje, no una penalización.';
        }
    }"
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-success-text">Evaluación de transferencia</p>
            <h2 id="transfer-check-title" class="mt-1 font-heading text-2xl font-bold text-text">Misión final: una salida que cambia</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary">Aplicá lo aprendido en situaciones que mezclan varios riesgos. Podés revisar tus respuestas y repetir sin límite.</p>
        </div>
        <span class="rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success-text">4 competencias</span>
    </div>

    @if ($transferCheckRecorded)
        <div class="mt-4 rounded-lg border border-success/30 bg-success/10 p-3 text-sm font-medium text-success-text">✓ Esta transferencia ya forma parte de tu Pasaporte Vial. Podés repetir la misión para practicar sin duplicar el registro.</div>
    @endif

    <div x-show="! complete" class="mt-5">
        <div class="mb-4 flex items-center justify-between text-xs font-bold text-text-secondary"><span>Desafío <span x-text="current + 1"></span> de 4</span><span x-text="situations[current].skill"></span></div>
        <template x-for="(situation, index) in situations" :key="situation.id">
            <fieldset x-show="current === index">
                <legend class="font-heading text-lg font-bold leading-7 text-text" x-text="situation.text"></legend>
                <div class="mt-4 grid gap-3">
                    <template x-for="option in situation.options" :key="option.id">
                        <button type="button" @click="choose(situation, option)" class="min-h-12 rounded-lg border px-4 py-3 text-left text-sm font-medium focus-visible:outline-none focus-visible:shadow-focus" :class="answers[situation.id]?.id === option.id ? 'border-success bg-success/10 text-success-text' : 'border-border bg-background text-text hover:border-primary'" x-text="option.label"></button>
                    </template>
                </div>
            </fieldset>
        </template>
        <div class="mt-5 flex justify-between gap-3">
            <button type="button" x-show="current > 0" @click="current--" class="min-h-11 rounded-md border border-primary px-4 text-sm font-bold text-primary">← Anterior</button>
            <button type="button" x-show="current < 3" @click="current++" :disabled="! answers[situations[current].id]" class="ml-auto min-h-11 rounded-md bg-primary px-5 text-sm font-bold text-white disabled:opacity-40">Siguiente →</button>
            <button type="button" x-show="current === 3" @click="complete = true" :disabled="answered !== 4" class="ml-auto min-h-11 rounded-md bg-success px-5 text-sm font-bold text-white disabled:opacity-40">Ver informe final</button>
        </div>
    </div>

    <div x-show="complete" x-cloak class="mt-5">
        <div class="rounded-lg bg-success/10 p-4 text-center"><p class="font-heading text-4xl font-bold text-success-text"><span x-text="score"></span>/4</p><p class="mt-2 text-sm leading-6 text-text-secondary" x-text="message"></p></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-success/30 p-4"><p class="font-bold text-success-text">Fortalezas observadas</p><p class="mt-2 text-sm text-text-secondary" x-text="strengths.length ? strengths.join(' · ') : 'Repetí la misión para descubrirlas con retroalimentación.'"></p></div>
            <div class="rounded-lg border border-warning/30 p-4"><p class="font-bold text-warning-text">Áreas para reforzar</p><p class="mt-2 text-sm text-text-secondary" x-text="practice.length ? practice.join(' · ') : 'Ninguna en este intento. Practicá ahora en otro contexto.'"></p></div>
        </div>
        <p class="mt-3 text-xs leading-5 text-text-secondary">Este chequeo formativo no modifica tu certificado. Podés registrarlo como evidencia de transferencia en el Pasaporte Vial.</p>
        @unless ($transferCheckRecorded)
        <form method="POST" action="{{ route('courses.transfer-check.store', $enrollmentId) }}" class="mt-4">
            @csrf
            <template x-for="situation in situations" :key="'evidence-'+situation.id">
                <input type="hidden" :name="'answers['+situation.id+']'" :value="answers[situation.id]?.id ?? ''">
            </template>
            <button type="submit" class="min-h-11 rounded-md bg-primary px-5 text-sm font-bold text-white hover:bg-secondary">Guardar en mi Pasaporte Vial</button>
        </form>
        @endunless
        <button type="button" @click="current = 0; complete = false; answers = {}" class="mt-3 min-h-11 text-sm font-bold text-primary underline underline-offset-2">Intentar con otro razonamiento</button>
    </div>
</section>
