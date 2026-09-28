@php($scenario = $block['payload'])
<section class="rounded-lg border-2 border-primary bg-surface p-5 space-y-5" x-data="passengerPreview3d(@js($scenario['choices']), @js($passengerMode), @js($block['id']))" aria-labelledby="scenario-{{ $block['id'] }}"
    @isset($courseLessonPage) x-effect="if (lessonPage !== {{ $courseLessonPage }} && ready && !paused) togglePause()" @endisset>
    <h3 class="text-xl font-bold" id="scenario-{{ $block['id'] }}">{{ $scenario['title'] }}</h3>
    <p class="text-lg leading-relaxed">{{ $scenario['context'] }}</p>
    <div class="overflow-hidden rounded-lg border border-border">
        <div x-ref="viewport" style="height:clamp(320px,48vw,500px);position:relative;background:#cee7f5">
            <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center" style="color:#12324a">
                <p x-text="error || 'Observá a Luna, su acompañante y el acceso al vehículo.'"></p>
                <button type="button" class="rounded-lg bg-primary px-5 py-3 text-white font-bold" @click="open()" :disabled="loading" x-text="loading ? 'Preparando escena…' : 'Abrir práctica 3D'"></button>
            </div>
        </div>
        <div x-show="ready" x-cloak class="flex flex-wrap gap-3 p-4">
            <button type="button" class="rounded border border-border p-3" @click="togglePause()" x-text="paused ? 'Reanudar movimiento' : 'Pausar movimiento'"></button>
            <label>Cámara <select class="rounded border border-border bg-surface p-3" @change="changeView($event.target.value)"><option value="overview">Vista general</option><option value="detail">Ver acceso de cerca</option></select></label>
            <button type="button" class="rounded border border-border p-3" @click="replay()">Repetir escena</button>
        </div>
        <p x-show="caption" x-cloak class="p-4 text-lg leading-relaxed" role="status" aria-live="polite" x-text="caption"></p>
    </div>
    <p class="text-lg font-semibold">{{ $scenario['prompt'] }}</p>
    @include('courses.blocks.editorial-decision-feedback')
    <p class="text-sm text-text-secondary">Maqueta educativa: los movimientos se simplifican y las decisiones de riesgo se detienen sin mostrar impactos. Con movimiento reducido se muestra el resultado sin animación.</p>
    <details><summary class="cursor-pointer font-semibold">Alternativa accesible</summary><p class="mt-4 text-lg leading-relaxed">{{ $scenario['accessible_text'] }} Podés responder sin abrir el 3D; la explicación aparece al elegir.</p></details>
</section>
