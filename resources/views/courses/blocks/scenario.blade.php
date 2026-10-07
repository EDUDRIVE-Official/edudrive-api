@php
    $scenario = $block['payload'];
    $context = \Illuminate\Support\Str::lower($scenario['context']);
    $actor = str_contains($context, 'biciclet') || str_contains($context, 'pedal') || str_contains($context, 'ciclista') ? '🚲' : '🚶';
    $hazard = match (true) {
        str_contains($context, 'lluv') || str_contains($context, 'mojad') || str_contains($context, 'agua') => ['🌧️', 'Lluvia o superficie mojada'],
        str_contains($context, 'autobús') || str_contains($context, 'bus ') => ['🚌', 'Autobús y movimientos ocultos'],
        str_contains($context, 'motociclet') || str_contains($context, 'moto ') => ['🏍️', 'Motocicleta en movimiento'],
        str_contains($context, 'camión') || str_contains($context, 'vehículo grande') => ['🚚', 'Vehículo grande y punto ciego'],
        str_contains($context, 'obra') || str_contains($context, 'construcción') || str_contains($context, 'bloquead') => ['🚧', 'Obstrucción o cambio de ruta'],
        str_contains($context, 'oscur') || str_contains($context, 'anoche') || str_contains($context, 'luz') => ['🌙', 'Visibilidad reducida'],
        str_contains($context, 'teléfono') || str_contains($context, 'mensaje') || str_contains($context, 'audífono') => ['📱', 'Distracción'],
        str_contains($context, 'casco') => ['🪖', 'Equipo de protección'],
        str_contains($context, 'señal') || str_contains($context, 'semáforo') => ['🚦', 'Señal y trayectoria'],
        str_contains($context, 'automóvil') || str_contains($context, 'vehículo') => ['🚗', 'Vehículo y trayectoria posible'],
        default => ['⚠️', 'Condición que requiere atención'],
    };
    $incidentScenes = ['Bicicleta caída' => 'fallen-bicycle', 'Cable desconocido' => 'unknown-cable', 'Mensaje incompleto' => 'incomplete-message', 'Ubicación privada' => 'private-location'];
    $visibleCycling3dTitles = [
        'Dos caminos al parque', 'La ruta cambia con la hora', 'Bajada mojada', 'Subida con carga',
        'Vehículo que podría girar', 'Salida desde una calle lateral', 'Autobús detenido', 'Pasajero que puede descender',
        'Paso bloqueado', 'La batería de la luz se agota', 'Recorrido escolar cambiante', 'Intersección con giro',
        'Falla durante el viaje', 'El freno que casi funciona', 'Una llanta pierde aire', 'Regreso más tarde de lo previsto',
        'Sombra después del sol', 'La puerta inesperada', 'Objeto que cae al pedalear', 'Casco prestado',
        'Casco después de una caída', 'Giro mientras frenás', 'No puede soltar una mano', 'Grava en la curva',
        'Tapa metálica mojada', 'Bolsa en el manubrio', 'Bicicleta demasiado grande', 'Autobús antes de la esquina',
        'Camión que retrocede', 'Salida con cambio de clima', 'Puerta y superficie mojada', 'Grupo con audífonos',
    ];
@endphp
@if (in_array($scenario['title'], ['Vehículo aún en movimiento', 'Ascenso junto a la calzada', 'Bajar entre vehículos', 'Después del autobús'], true) && collect($scenario['choices'])->pluck('id')->sort()->values()->all() === ['copiar', 'impulso', 'segura'])
    @php($passengerMode = match($scenario['title']) { 'Vehículo aún en movimiento' => 'moving', 'Ascenso junto a la calzada' => 'boarding', 'Bajar entre vehículos' => 'descent', default => 'after' })
    @include('courses.blocks.passenger-preview-3d', ['block' => $block, 'passengerMode' => $passengerMode])
@elseif (in_array($scenario['title'], ['Mensaje al acercarte al cruce', 'El mapa cambia la ruta', 'Audífonos y autobús', 'Mucho ruido en la calle', 'Saliste con enojo'], true))
    @include('courses.blocks.message-crossing-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif (in_array($scenario['title'], $visibleCycling3dTitles, true))
    @include('courses.blocks.visible-cycling-scenario-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'El autobús en la parada')
    @include('courses.blocks.bus-stop-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Movimientos que se cruzan')
    @include('courses.blocks.crossing-movements-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Dos caminos a la escuela')
    @include('courses.blocks.route-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La rampa está bloqueada')
    @include('courses.blocks.blocked-ramp-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La señal cambió')
    @include('courses.blocks.signal-change-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'El atajo entre automóviles')
    @include('courses.blocks.parked-cars-shortcut-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La esquina con poca visibilidad')
    @include('courses.blocks.low-visibility-corner-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Un carril se detuvo')
    @include('courses.blocks.hidden-lane-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Salida bajo la lluvia')
    @include('courses.blocks.rainy-crossing-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Regreso al anochecer')
    @include('courses.blocks.dusk-crossing-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Parece que está lejos')
    @include('courses.blocks.perceived-distance-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Bicicleta en bajada')
    @include('courses.blocks.downhill-bicycle-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'El reto del balón')
    @include('courses.blocks.ball-challenge-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La señal cambia durante el cruce')
    @include('courses.blocks.crossing-signal-transition-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Vehículo que gira')
    @include('courses.blocks.turning-vehicle-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Una sirena cambia el plan')
    @include('courses.blocks.ambulance-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La salida del centro educativo')
    @include('courses.blocks.school-exit-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La acera bloqueada')
    @include('courses.blocks.blocked-sidewalk-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La señal y el vehículo silencioso')
    @include('courses.blocks.silent-vehicle-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'La pelota junto al portón')
    @include('courses.blocks.gate-ball-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Marcas oscuras en la vía')
    @include('courses.blocks.dark-road-marks-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Camino rural con curva')
    @include('courses.blocks.rural-curve-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Entre vehículos estacionados')
    @include('courses.blocks.between-parked-vehicles-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Fila frente a la escuela')
    @include('courses.blocks.school-queue-door-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Ruedas giradas en la esquina')
    @include('courses.blocks.turned-wheels-corner-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Aguacero al salir')
    @include('courses.blocks.downpour-exit-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Apagón en el barrio')
    @include('courses.blocks.neighborhood-blackout-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Bus que ya llegó')
    @include('courses.blocks.departing-bus-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Espacio que desaparece')
    @include('courses.blocks.disappearing-space-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Parada con visibilidad bloqueada')
    @include('courses.blocks.blocked-stop-rain-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Ruta accesible interrumpida')
    @include('courses.blocks.blocked-sidewalk-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Cambio inesperado')
    @include('courses.blocks.ambulance-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Señal desconocida')
    @include('courses.blocks.unknown-sign-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Conos frente a la escuela')
    @include('courses.blocks.school-cones-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Luz favorable, cruce ocupado')
    @include('courses.blocks.occupied-crossing-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Señales que parecen contradecirse')
    @include('courses.blocks.conflicting-signals-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Paso peatonal')
    @include('courses.blocks.real-crosswalk-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Cruce de ciclovía')
    @include('courses.blocks.cycle-track-crossing-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@elseif (isset($incidentScenes[$scenario['title']]) && collect($scenario['choices'])->pluck('id')->sort()->values()->all() === ['exponer', 'grabar', 'segura'])
    @include('courses.blocks.incident-decision-3d', ['scenario' => $scenario, 'block' => $block, 'sceneKey' => $incidentScenes[$scenario['title']], 'answerField' => $answerField ?? null])
@elseif ($scenario['title'] === 'Te hacen una señal')
    @include('courses.blocks.courtesy-signal-decision-3d', ['scenario' => $scenario, 'block' => $block, 'answerField' => $answerField ?? null])
@else
<section
    class="rounded-lg border-2 border-primary bg-surface p-5"
    x-data="{ choices: @js($scenario['choices']), selected: null, loading: false,
        choose(id) { this.selected = this.choices.find(choice => choice.id === id); this.$dispatch('scenario-answered', { id: @js($block['id']), correct: this.selected?.correct === true }); },
        retry() { this.selected = null; this.$dispatch('scenario-answered', { id: @js($block['id']), correct: false }); }
    }"
    aria-labelledby="scenario-{{ $block['id'] }}"
>
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Práctica de decisión</p>
    <h4 id="scenario-{{ $block['id'] }}" class="mt-1 font-heading text-lg font-bold">{{ $scenario['title'] }}</h4>
    <div class="mt-4 overflow-hidden rounded-lg border border-border bg-background">
        <div class="relative h-28 overflow-hidden bg-gradient-to-b from-sky-100 to-sky-50" role="img" aria-label="Representación de la situación: {{ $hazard[1] }}.">
            <div class="absolute inset-x-0 bottom-0 h-5 bg-stone-300" aria-hidden="true"></div>
            <div class="absolute inset-x-0 bottom-5 h-12 bg-slate-600" aria-hidden="true"><div class="absolute left-0 top-1/2 w-full border-t border-dashed border-amber-300"></div></div>
            <span class="absolute bottom-1 left-[14%] z-10 text-4xl" aria-hidden="true">{{ $actor }}</span>
            <span class="scenario-scene-hazard absolute bottom-8 right-[16%] text-5xl drop-shadow-sm" aria-hidden="true">{{ $hazard[0] }}</span>
            <span class="absolute right-2 top-2 rounded-full bg-surface/95 px-3 py-1 text-[11px] font-bold text-text shadow-sm">{{ $hazard[1] }}</span>
        </div>
        <p class="p-4 text-sm leading-6 text-text-secondary">{{ $scenario['context'] }}</p>
    </div>
    <p class="mt-4 font-semibold text-text">{{ $scenario['prompt'] }}</p>

    @include('courses.blocks.editorial-decision-feedback')

    <details class="mt-4 text-sm text-text-secondary">
        <summary class="cursor-pointer font-medium">Alternativa accesible</summary>
        <p class="mt-2 leading-6">{{ $scenario['accessible_text'] }}</p>
    </details>
</section>
@endif
