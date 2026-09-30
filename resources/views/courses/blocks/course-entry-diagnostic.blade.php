@php $entryDiagnosticRecorded = $entryDiagnosticRecorded ?? false; @endphp
<section
    id="entry-diagnostic"
    class="rounded-xl border-2 border-primary/40 bg-surface p-5 shadow-sm"
    aria-labelledby="entry-diagnostic-title"
    x-data="{
        started: false,
        finished: false,
        current: 0,
        answers: {},
        questions: [
            { id: 'place', text: 'Hay un paso marcado, pero un vehículo estacionado impide ver un carril. ¿Qué información necesitás antes de cruzar?', options: [
                { id: 'signal', label: 'Solo confirmar que la señal permita pasar', safe: false },
                { id: 'view', label: 'Recuperar visibilidad de todos los carriles y movimientos', safe: true },
                { id: 'follow', label: 'Esperar a que otra persona cruce primero y seguirla', safe: false }
            ]},
            { id: 'change', text: 'La señal peatonal es favorable, pero aparece un vehículo que va a girar. ¿Qué hacés?', options: [
                { id: 'right', label: 'Avanzo porque tengo prioridad', safe: false },
                { id: 'check', label: 'Espero y confirmo que su trayectoria no ocupe el cruce', safe: true },
                { id: 'run', label: 'Cruzo más rápido para pasar primero', safe: false }
            ]},
            { id: 'pressure', text: 'Tu grupo cruza por un atajo sin visibilidad y te llama desde el otro lado. ¿Qué decidís?', options: [
                { id: 'group', label: 'Los sigo para no quedarme atrás', safe: false },
                { id: 'route', label: 'Uso el punto seguro y les aviso dónde nos encontramos', safe: true },
                { id: 'edge', label: 'Me acerco al borde para decidir desde ahí', safe: false }
            ]}
        ],
        choose(question, option) {
            this.answers[question.id] = option;
        },
        get answered() { return Object.keys(this.answers).length; },
        get safeCount() { return Object.values(this.answers).filter(answer => answer.safe).length; },
        get recommendation() {
            if (this.safeCount === 3) return 'Ya mostrás un criterio inicial sólido. El curso te ayudará a transferirlo a lluvia, oscuridad, presión y situaciones complejas.';
            if (this.safeCount === 2) return 'Tenés buenas bases. Prestá especial atención a recuperar visibilidad y reconstruir el plan cuando algo cambia.';
            return 'Este curso empieza exactamente donde lo necesitás: elegir un lugar protegido, observar todas las trayectorias y decidir sin prisa.';
        }
    }"
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-primary">Antes de comenzar</p>
            <h2 id="entry-diagnostic-title" class="mt-1 font-heading text-xl font-bold text-text">Descubrí cómo tomás decisiones hoy</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-text-secondary">Son tres situaciones sin nota. No bloquean el curso: sirven para activar lo que ya sabés y recomendarte en qué fijarte.</p>
        </div>
        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $learnerStage['identity'] }}</span>
    </div>

    @if ($entryDiagnosticRecorded)
        <div class="mt-4 rounded-lg border border-success/30 bg-success/10 p-3 text-sm font-medium text-success-text">✓ Tu punto de partida ya está guardado en el Pasaporte Vial. Podés repetir estas situaciones para practicar; no se duplicará la evidencia.</div>
    @endif

    <button x-show="! started" type="button" @click="started = true" class="mt-4 min-h-11 rounded-md bg-primary px-5 py-2 text-sm font-bold text-white hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus">Iniciar diagnóstico</button>

    <div x-show="started && ! finished" x-cloak class="mt-5">
        <div class="mb-4 flex items-center justify-between gap-3 text-xs font-bold text-text-secondary">
            <span>Situación <span x-text="current + 1"></span> de <span x-text="questions.length"></span></span>
            <span><span x-text="answered"></span> respondidas</span>
        </div>
        <template x-for="(question, index) in questions" :key="question.id">
            <fieldset x-show="current === index">
                <legend class="font-heading text-lg font-bold leading-7 text-text" x-text="question.text"></legend>
                <div class="mt-4 grid gap-3">
                    <template x-for="option in question.options" :key="option.id">
                        <button type="button" @click="choose(question, option)" class="min-h-12 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors focus-visible:outline-none focus-visible:shadow-focus" :class="answers[question.id]?.id === option.id ? 'border-primary bg-primary/10 text-primary' : 'border-border bg-background text-text hover:border-primary'" x-text="option.label"></button>
                    </template>
                </div>
            </fieldset>
        </template>
        <div class="mt-5 flex flex-wrap justify-between gap-3">
            <button type="button" @click="current--" x-show="current > 0" class="min-h-11 rounded-md border border-primary px-4 text-sm font-bold text-primary">← Anterior</button>
            <button type="button" @click="current++" x-show="current < questions.length - 1" :disabled="! answers[questions[current].id]" class="ml-auto min-h-11 rounded-md bg-primary px-5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Siguiente →</button>
            <button type="button" @click="finished = true" x-show="current === questions.length - 1" :disabled="answered !== questions.length" class="ml-auto min-h-11 rounded-md bg-success px-5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Ver mi punto de partida</button>
        </div>
    </div>

    <div x-show="finished" x-cloak class="mt-5 rounded-lg border border-success/30 bg-success/10 p-4" role="status" aria-live="polite">
        <p class="text-xs font-bold uppercase tracking-wide text-success-text">Tu punto de partida</p>
        <p class="mt-2 font-heading text-2xl font-bold text-text"><span x-text="safeCount"></span> de 3 decisiones conservaron margen</p>
        <p class="mt-2 text-sm leading-6 text-text-secondary" x-text="recommendation"></p>
        <p class="mt-3 text-xs leading-5 text-text-secondary">Podés guardar este resultado como punto de partida. No es una nota y no aumenta ni reduce el dominio de una competencia.</p>
        @unless ($entryDiagnosticRecorded)
        <form method="POST" action="{{ route('courses.entry-diagnostic.store', $enrollmentId) }}" class="mt-4">
            @csrf
            <template x-for="question in questions" :key="'diagnostic-'+question.id">
                <input type="hidden" :name="'answers['+question.id+']'" :value="answers[question.id]?.id ?? ''">
            </template>
            <button type="submit" class="min-h-11 rounded-md bg-primary px-5 text-sm font-bold text-white hover:bg-secondary">Guardar mi punto de partida</button>
        </form>
        @endunless
        <button type="button" @click="started = false; finished = false; current = 0; answers = {}" class="mt-3 min-h-11 text-sm font-bold text-primary underline underline-offset-2">Volver a intentarlo</button>
    </div>
</section>
