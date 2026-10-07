@php
    $fbSteps = [
        'segura' => [
            'Te alejás de la calzada y te ubicás en un lugar protegido.',
            'Desde ahí pedís que una persona adulta o un servicio de ayuda intervenga.',
            'La ayuda llega sin sumar otra persona en riesgo y el acceso queda libre.',
        ],
        'exponer' => [
            'Sentís el impulso de correr hacia la persona caída.',
            'Al entrar en la calzada, un vehículo tiene que frenar de golpe.',
            'Ahora hay dos personas expuestas: el peligro aumentó.',
        ],
        'grabar' => [
            'Sacás el teléfono para grabar la escena.',
            'Pasa el tiempo y la persona sigue sin ayuda mientras circulan vehículos.',
            'Grabar no ayuda: primero se pide ayuda.',
        ],
    ];
@endphp
<section class="rounded-lg border-2 border-primary bg-surface p-5" x-data="fallenBicycleDecision3d(@js($scenario['choices']), @js($block['id']))" aria-labelledby="scenario-{{ $block['id'] }}">
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Práctica de decisión · 3D</p>
    <h4 id="scenario-{{ $block['id'] }}" class="mt-1 font-heading text-lg font-bold">{{ $scenario['title'] }}</h4>
    <div class="mt-4 overflow-hidden rounded-lg border border-border bg-background">
        <div x-ref="viewport" style="height:clamp(320px,48vw,500px);position:relative;background:#c8e4ef">
            <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center" style="color:#12324a">
                <p class="max-w-lg text-sm" x-text="error || 'Abrí la escena 3D para observar qué pasa alrededor de la persona caída mientras el tránsito sigue circulando.'"></p>
                <button type="button" class="min-h-11 rounded-md bg-primary px-5 font-bold text-white" @click="open()" :disabled="loading" x-text="loading ? 'Preparando práctica…' : 'Abrir práctica 3D'"></button>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-border p-3">
            <button type="button" x-show="ready" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="togglePause()" x-text="paused ? '▶ Reanudar movimiento' : '⏸ Pausar movimiento'"></button>
            <template x-if="selected">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="goStep(step - 1)" :disabled="step === 0" aria-label="Paso anterior">← Anterior</button>
                    <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="goStep(step + 1)" :disabled="step === 2" aria-label="Paso siguiente">Siguiente →</button>
                    <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="replay()">↺ Repetir</button>
                    <span class="text-xs text-text-secondary" x-text="`Paso ${step + 1} de 3`"></span>
                </div>
            </template>
            <label class="flex items-center gap-2 text-sm" x-show="ready">Cámara
                <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)">
                    <option value="overview">Vista general</option>
                    <option value="pedestrian">Desde vos</option>
                    <option value="driver">Desde el vehículo</option>
                </select>
            </label>
        </div>
        <div class="border-t border-border p-4" role="status" aria-live="polite" aria-atomic="true">
            <p class="text-sm leading-6 text-text-secondary" x-show="!selected">{{ $scenario['context'] }}</p>
            @foreach ($fbSteps as $id => $texts)
                <template x-if="selected?.id === @js($id)">
                    <p class="text-sm font-medium leading-6 text-text" x-text="@js($texts)[step]"></p>
                </template>
            @endforeach
        </div>
    </div>
    <p class="mt-4 font-semibold text-text">{{ $scenario['prompt'] }}</p>

    @include('courses.blocks.editorial-decision-feedback', ['answerField' => $answerField ?? null])

    <p class="mt-4 text-xs text-text-secondary">Verde: lugar protegido y acceso libre para la ayuda. Rojo: exposición que aumenta el peligro. Amarillo: persona que necesita ayuda.</p>
    <details class="mt-4 text-sm text-text-secondary">
        <summary class="cursor-pointer font-medium">Alternativa accesible</summary>
        <p class="mt-2 leading-6">{{ $scenario['accessible_text'] }}</p>
    </details>
</section>
