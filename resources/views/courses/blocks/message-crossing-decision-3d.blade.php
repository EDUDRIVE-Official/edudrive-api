@php($isAngry = $scenario['title'] === 'Saliste con enojo')
@php($isMap = $scenario['title'] === 'El mapa cambia la ruta')
@php($isNoise = $scenario['title'] === 'Mucho ruido en la calle')
@php($isBus = $scenario['title'] === 'Audífonos y autobús')
<section class="rounded-lg border-2 border-primary bg-surface p-5" x-data="messageCrossingDecision3d(@js($scenario['choices']), @js($block['id']), @js($isMap), @js($isBus), @js($isNoise), @js($isAngry))" aria-labelledby="scenario-{{ $block['id'] }}">
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Práctica de decisión · 3D</p>
    <h4 id="scenario-{{ $block['id'] }}" class="mt-1 font-heading text-lg font-bold">{{ $scenario['title'] }}</h4>
    <div class="mt-4 overflow-hidden rounded-lg border border-border bg-background">
        <div x-ref="viewport" style="height:clamp(380px,54vw,580px);position:relative;background:#dbe9ee">
            @if($isMap)
            <div x-show="ready" x-cloak class="pointer-events-none absolute left-3 top-3 z-10 max-w-[230px] rounded-lg border border-border bg-surface p-3 shadow-md">
                <p class="text-xs font-semibold uppercase text-text-secondary">Indicación del mapa</p>
                <p class="mt-1 text-sm font-bold text-primary"><span aria-hidden="true" class="mr-2 text-2xl">↱</span> Girá a la derecha</p>
                <p class="mt-1 text-xs text-text-secondary">La ruta no autoriza el cruce.</p>
            </div>
            @endif
            <div x-show="!ready" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 p-6 text-center">
                <p class="max-w-lg text-sm" x-text="error || @js($isAngry ? 'Luna sale de una tienda después de una discusión. Observá su paso, su postura y cómo recupera la atención.' : ($isNoise ? 'Una obra junto a la acera dificulta escuchar el tránsito. Observá cómo Luna adapta su distancia y su mirada.' : ($isBus ? 'Bajá del autobús con Luna y observá cómo la llamada y los audífonos afectan su decisión.' : ($isMap ? 'Un giro en el mapa y una esquina desconocida. Observá cómo Luna separa la orientación de la decisión vial.' : 'Una notificación, una esquina y una decisión. Observá dónde se detiene Luna antes de mirar el teléfono.'))))"></p>
                <button type="button" class="min-h-11 rounded-md bg-primary px-5 font-bold text-white" @click="open()" :disabled="loading" x-text="loading ? 'Preparando escena…' : 'Explorar escena 3D'"></button>
            </div>
        </div>
        <p x-show="ready" x-cloak class="border-t border-border px-4 py-3 text-sm font-semibold" role="status" aria-live="polite" x-text="caption"></p>
        <div x-show="ready" x-cloak class="flex flex-wrap items-center gap-2 border-t border-border p-3">
            <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="togglePause()" :aria-pressed="paused" x-text="paused ? 'Reanudar' : 'Pausar'"></button>
            <label class="flex items-center gap-2 text-sm">Cámara <select class="min-h-11 rounded-md border border-border bg-surface px-3" :value="view" @change="changeView($event.target.value)"><option value="overview">{{ $isAngry ? 'Calle y tienda' : ($isNoise ? 'Calle y obra' : 'Intersección') }}</option><option value="detail">Cerca de Luna</option><option value="pedestrian">Desde su mirada</option></select></label>
            <button type="button" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="replay()">Repetir</button>
            <button type="button" x-show="selected" class="min-h-11 rounded-md border border-border px-3 text-sm" @click="retry()">Otra decisión</button>
        </div>
        <p class="p-4 text-sm leading-6 text-text-secondary">{{ $scenario['context'] }}</p>
    </div>
    <p class="mt-4 font-semibold">{{ $scenario['prompt'] }}</p>
    <div class="mt-4 grid gap-3">
        @foreach ($scenario['choices'] as $choice)
        <button type="button" class="min-h-[48px] rounded-md border border-border px-4 py-3 text-left text-sm font-medium hover:border-primary hover:bg-background focus-visible:outline-none focus-visible:shadow-focus" @click="choose(@js($choice['id']))" :aria-pressed="selected?.id === @js($choice['id'])" :class="selected?.id === @js($choice['id']) ? (selected.correct ? 'border-success bg-success/10' : 'border-warning bg-warning/10') : ''"><span class="flex items-center gap-3"><span aria-hidden="true" class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-current text-xs font-bold">{{ chr(64 + $loop->iteration) }}</span><span>{{ $choice['label'] }}</span></span></button>
        @endforeach
    </div>
    @isset($answerField)<input type="hidden" name="scenario_answers[{{ $block['id'] }}]" :value="selected?.id ?? ''">@endisset
    <div x-show="selected" x-cloak class="mt-4 rounded-md bg-background p-4" role="status" aria-live="polite"><p class="font-semibold" :class="selected?.correct ? 'text-success' : 'text-warning-text'" x-text="selected?.correct ? 'Recuperá atención y margen antes de avanzar.' : 'Antes de avanzar, necesitás comprobar el entorno.'"></p><p class="mt-2 text-sm leading-6" x-text="selected?.feedback"></p></div>
    <details class="mt-4 text-sm text-text-secondary"><summary class="cursor-pointer font-medium">Alternativa accesible</summary><p class="mt-2 leading-6">{{ $scenario['accessible_text'] }}</p><p class="mt-2">@if($isAngry) Luna abre la puerta y sale de una tienda con postura tensa y paso rápido. La calle tiene dos carriles en el mismo sentido. A: sigue sin recuperar atención. B: se aparta del borde, se detiene, respira y observa antes de continuar. C: acelera y se acerca al borde; la escena termina sin entrar en la calle. @elseif($isNoise) Una calle recta tiene un carril por sentido y una obra vallada junto a la acera. Los pulsos visuales representan el ruido. A: Luna se acerca al borde sin recuperar información. B: se aleja por la acera y comprueba ambos sentidos desde un lugar despejado. C: entra con prisa a la calzada; la escena se detiene para señalar el riesgo. @elseif($isBus) Luna baja por la puerta hacia la acera. A: continúa hablando y se asoma por detrás del autobús. B: pausa la llamada, guarda los audífonos y busca un punto visible por la acera. C: se apresura y sale a la calzada rodeando la parte trasera; la escena se detiene para mostrar el peligro, sin representar un choque. @elseif($isMap) A: gira mirando el mapa mientras camina. B: se detiene en el interior de la acera, revisa la ruta, guarda el teléfono y mira a ambos lados antes de decidir. C: sigue a otra persona sin comprobar su propio recorrido. La indicación del mapa no autoriza un cruce. @else A: Luna lee mientras se acerca lentamente al borde. B: se aparta hacia el interior de la acera, se detiene, revisa el mensaje y guarda el teléfono. C: lee con prisa y llega al borde sin haber comprobado el tránsito. La señal peatonal permanece roja. @endif</p></details>
</section>
