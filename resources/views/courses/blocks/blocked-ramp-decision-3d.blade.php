<section class="rounded-lg border-2 border-primary bg-surface p-5"
         x-data="blockedRampDecision3d(@js($scenario['choices']), @js($block['id']), @js(($scenario['stop_at_decision_point'] ?? false) === true))"
         aria-labelledby="scenario-{{ $block['id'] }}">
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Práctica de decisión · 3D</p>
    <h4 id="scenario-{{ $block['id'] }}" class="mt-1 font-heading text-lg font-bold">{{ $scenario['title'] }}</h4>

    <div class="mt-4 overflow-hidden rounded-lg border border-border bg-background">
        <div x-ref="viewport" style="height:clamp(320px,50vw,520px);position:relative;background:#c9e3ef">
            <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-gradient-to-b from-sky-100 to-emerald-50 p-6 text-center text-slate-900">
                <div class="text-5xl" aria-hidden="true">♿ <span class="mx-3 text-3xl text-primary">→</span> 🪴 <span class="mx-3 text-3xl text-primary">→</span> 🚦</div>
                <p class="max-w-lg text-sm text-slate-700" x-text="error || 'Abrí la escena para identificar la obstrucción, respetar la autonomía y comparar las consecuencias.'"></p>
                <button type="button" class="min-h-11 rounded-md bg-primary px-5 font-bold text-white disabled:opacity-60" @click="open()" :disabled="loading" x-text="loading ? 'Preparando práctica…' : 'Abrir práctica 3D'"></button>
            </div>
            <div x-show="ready && selected" x-cloak class="pointer-events-none absolute left-3 top-3 rounded-full bg-slate-900/85 px-3 py-2 text-xs font-bold text-white shadow-lg sm:text-sm">
                <span x-show="selected?.id === 'preguntar'">Autonomía: preguntar y decidir juntos</span>
                <span x-show="selected?.id === 'empujar'">Sin consentimiento: la ayuda también puede crear riesgo</span>
                <span x-show="selected?.id === 'calzada'">Exposición: la calzada no sustituye una rampa accesible</span>
            </div>
            <div x-show="ready && view === 'overview'" x-cloak class="pointer-events-none absolute bottom-3 right-3 rounded-md bg-slate-900/80 px-3 py-2 text-xs text-white">Arrastrá para observar · rueda para acercar</div>
            <div x-show="ready && view === 'traveler'" x-cloak class="pointer-events-none absolute bottom-3 right-3 rounded-md bg-slate-900/80 px-3 py-2 text-xs text-white">La cámara acompaña el recorrido</div>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-border p-3">
            <button type="button" x-show="ready && selected" x-cloak class="min-h-11 rounded-md border border-border px-3 text-sm" @click="togglePause()" x-text="paused ? 'Reanudar escena' : 'Pausar escena'"></button>
            <button type="button" x-show="ready && selected" x-cloak class="min-h-11 rounded-md border border-border px-3 text-sm" @click="replay()">Repetir consecuencia</button>
            <label x-show="ready" x-cloak class="flex items-center gap-2 text-sm">Cámara
                <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)">
                    <option value="overview">Vista general</option>
                    <option value="traveler">Acompañar el recorrido</option>
                </select>
            </label>
            <button type="button" x-show="selected" x-cloak class="min-h-11 rounded-md border border-border px-3 text-sm" @click="retry()">Intentar otra decisión</button>
        </div>
        <p class="p-4 text-sm leading-6 text-text-secondary">{{ $scenario['context'] }}</p>
    </div>

    <p class="mt-4 font-semibold text-text">{{ $scenario['prompt'] }}</p>
    @isset($courseLessonPage)
        <span hidden x-effect="if (lessonPage !== {{ $courseLessonPage }} && ready && !paused) togglePause()"></span>
    @endisset
    @if($uniformEditorialFeedback ?? false)
        @include('courses.blocks.editorial-decision-feedback')
    @else
    <div class="mt-4 grid gap-3">
        @foreach ($scenario['choices'] as $choice)
            <button type="button"
                    class="min-h-[48px] rounded-md border border-border px-4 py-3 text-left text-sm font-medium hover:border-primary hover:bg-background focus-visible:outline-none focus-visible:shadow-focus"
                    @click="choose(@js($choice['id']))" :disabled="loading"
                    :aria-pressed="selected?.id === @js($choice['id'])"
                    :class="selected?.id === @js($choice['id']) ? (selected.correct ? 'border-success bg-success/10' : 'border-warning bg-warning/10') : ''">
                <span class="flex items-center gap-3">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-current text-xs font-bold" aria-hidden="true">{{ chr(64 + $loop->iteration) }}</span>
                    <span>{{ $choice['label'] }}</span>
                </span>
            </button>
        @endforeach
    </div>

    @isset($answerField)<input type="hidden" name="scenario_answers[{{ $block['id'] }}]" :value="selected?.id ?? ''">@endisset

    <div x-show="selected" x-cloak class="mt-4 rounded-md bg-background p-4" role="status" aria-live="polite">
        <p class="font-semibold" :class="selected?.correct ? 'text-success' : 'text-warning-text'" x-text="selected?.correct ? '¡Respetaste autonomía y seguridad!' : 'La intención de ayudar no elimina el riesgo'"></p>
        <p class="mt-2 text-sm leading-6 text-text" x-text="selected?.feedback"></p>
    </div>
    @endif

    <p class="mt-4 text-xs text-text-secondary">La línea verde muestra una alternativa protegida acordada con la persona. El círculo rojo identifica exposición o conflicto; es una ayuda educativa y no una medición.</p>
    <details class="mt-4 text-sm text-text-secondary"><summary class="cursor-pointer font-medium">Alternativa accesible</summary><p class="mt-2 leading-6">{{ $scenario['accessible_text'] }}</p></details>
</section>
