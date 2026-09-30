@php
    $scenes = [
        'MISION-CRUCE' => ['icon' => '🚶', 'moving' => '🚗', 'signal' => '🚦', 'sky' => 'from-sky-200 to-amber-100', 'accent' => 'bg-emerald-600', 'title' => 'Cruzar con información', 'description' => 'La persona espera, observa cada trayectoria y avanza únicamente cuando conserva margen.'],
        'MISION-RIESGO' => ['icon' => '🔎', 'moving' => '🏍️', 'signal' => '🚌', 'sky' => 'from-violet-200 to-sky-100', 'accent' => 'bg-violet-600', 'title' => 'Detectar lo que puede aparecer', 'description' => 'Los obstáculos esconden movimiento. El radar busca pistas antes de que el peligro quede cerca.'],
        'MISION-CONVIVENCIA' => ['icon' => '🤝', 'moving' => '🚲', 'signal' => '🚸', 'sky' => 'from-amber-100 to-emerald-100', 'accent' => 'bg-amber-500', 'title' => 'Compartir sin competir', 'description' => 'Señales, prioridades y movimientos predecibles permiten que todas las personas conserven espacio.'],
        'MISION-AUTOCUIDADO' => ['icon' => '🧠', 'moving' => '📱', 'signal' => '⏸️', 'sky' => 'from-rose-100 to-indigo-100', 'accent' => 'bg-rose-500', 'title' => 'Pausar para recuperar control', 'description' => 'La atención vuelve al recorrido cuando se retira el distractor y se reconoce el estado personal.'],
        'MISION-BICI' => ['icon' => '🚲', 'moving' => '✨', 'signal' => '🪖', 'sky' => 'from-cyan-100 to-lime-100', 'accent' => 'bg-cyan-600', 'title' => 'Preparar, comunicar y pedalear', 'description' => 'Equipo, visibilidad y control se comprueban antes de iniciar el movimiento.'],
        'MISION-RUTA-BICI' => ['icon' => '🗺️', 'moving' => '🚴', 'signal' => '↪️', 'sky' => 'from-emerald-100 to-blue-100', 'accent' => 'bg-blue-600', 'title' => 'Una ruta con plan B', 'description' => 'La ruta se compara antes y se reconstruye cuando cambian el clima, la superficie o los conflictos.'],
        'MISION-PASAJERO' => ['icon' => '🧍', 'moving' => '🚙', 'signal' => '🛡️', 'sky' => 'from-blue-100 to-amber-100', 'accent' => 'bg-blue-600', 'title' => 'Protección durante todo el viaje', 'description' => 'El viaje comienza en un lugar seguro y continúa con cinturón, orden y atención.'],
        'MISION-TRANSPORTE' => ['icon' => '🚏', 'moving' => '🚌', 'signal' => '🤝', 'sky' => 'from-amber-100 to-cyan-100', 'accent' => 'bg-amber-500', 'title' => 'Transporte compartido con criterio', 'description' => 'Esperar, abordar, viajar y responder a cambios son partes de una misma conducta segura.'],
        'MISION-MOTO-PREPARA' => ['icon' => '🪖', 'moving' => '🏍️', 'signal' => '🔧', 'sky' => 'from-orange-100 to-slate-200', 'accent' => 'bg-orange-600', 'title' => 'Preparación antes del movimiento', 'description' => 'Equipo, motocicleta y visibilidad se comprueban antes de entrar a la vía.'],
        'MISION-MOTO-MARGEN' => ['icon' => '🛡️', 'moving' => '🏍️', 'signal' => '🌧️', 'sky' => 'from-slate-200 to-blue-100', 'accent' => 'bg-slate-600', 'title' => 'Margen para anticipar y adaptarse', 'description' => 'La posición, la distancia y el plan B evitan quedar atrapado por un cambio.'],
        'MISION-CONDUCE-MARGEN' => ['icon' => '🧠', 'moving' => '🚙', 'signal' => '↔️', 'sky' => 'from-indigo-100 to-emerald-100', 'accent' => 'bg-indigo-600', 'title' => 'Conducir con tiempo y espacio', 'description' => 'La preparación, la velocidad y la distancia permiten responder antes de una emergencia.'],
        'MISION-CONDUCE-CUIDA' => ['icon' => '🤲', 'moving' => '🚗', 'signal' => '🚲', 'sky' => 'from-emerald-100 to-amber-100', 'accent' => 'bg-emerald-600', 'title' => 'Compartir y saber detenerse', 'description' => 'La conducción preventiva protege a quienes tienen menos estructura y adapta el plan cuando la capacidad disminuye.'],
        'MISION-MOVILIDAD-SOSTENIBLE' => ['icon' => '🌱', 'moving' => '🚲', 'signal' => '🚌', 'sky' => 'from-lime-100 to-sky-100', 'accent' => 'bg-lime-600', 'title' => 'Elegir, combinar y cuidar', 'description' => 'Una movilidad sostenible protege a las personas, conecta medios y reduce impactos evitables.'],
        'MISION-ESCUELA-SEGURA' => ['icon' => '🏫', 'moving' => '🚌', 'signal' => '🚸', 'sky' => 'from-amber-100 to-sky-100', 'accent' => 'bg-amber-600', 'title' => 'Una llegada escolar coordinada', 'description' => 'Cruces visibles, zonas libres y acuerdos compartidos protegen a toda la comunidad educativa.'],
        'MISION-INCIDENTE-SEGURO' => ['icon' => '🛡️', 'moving' => '🚑', 'signal' => '⚠️', 'sky' => 'from-rose-100 to-slate-100', 'accent' => 'bg-rose-600', 'title' => 'Proteger antes de acercarse', 'description' => 'Distancia, información clara y acceso libre permiten ayudar sin crear una segunda emergencia.'],
        'MISION-FAMILIA-VIAL' => ['icon' => '👨‍👩‍👧', 'moving' => '🚶', 'signal' => '💬', 'sky' => 'from-violet-100 to-amber-100', 'accent' => 'bg-violet-600', 'title' => 'Enseñar con ejemplo y preguntas', 'description' => 'La persona adulta protege, explica y observa sin sustituir el desarrollo del criterio.'],
        'MISION-MOVILIDAD-INCLUSIVA' => ['icon' => '♿', 'moving' => '🚶', 'signal' => '🤝', 'sky' => 'from-blue-100 to-violet-100', 'accent' => 'bg-blue-600', 'title' => 'Una ruta que incluye', 'description' => 'La accesibilidad se construye al retirar barreras y respetar la autonomía de cada persona.'],
        'MISION-CLIMA-NOCHE' => ['icon' => '🌧️', 'moving' => '🚗', 'signal' => '🌙', 'sky' => 'from-slate-300 to-indigo-200', 'accent' => 'bg-indigo-600', 'title' => 'Menos visibilidad, más margen', 'description' => 'Preparación, distancia y un plan alterno permiten responder cuando cambian la luz y el clima.'],
        'MISION-OBRAS-RUTA' => ['icon' => '🚧', 'moving' => '🚜', 'signal' => '↪️', 'sky' => 'from-orange-100 to-slate-200', 'accent' => 'bg-orange-600', 'title' => 'Leer una ruta que cambió', 'description' => 'Las señales temporales reorganizan el espacio y ayudan a construir un recorrido predecible.'],
        'MISION-MICROMOVILIDAD' => ['icon' => '🛹', 'moving' => '🛼', 'signal' => '🪖', 'sky' => 'from-fuchsia-100 to-cyan-100', 'accent' => 'bg-fuchsia-600', 'title' => 'Pequeñas ruedas, grandes decisiones', 'description' => 'Preparación, control y cortesía convierten el juego en movilidad responsable.'],
        'MISION-RURAL-FAUNA' => ['icon' => '🌿', 'moving' => '🐄', 'signal' => '↔️', 'sky' => 'from-lime-100 to-amber-100', 'accent' => 'bg-lime-700', 'title' => 'Anticipar en caminos vivos', 'description' => 'La superficie, la visibilidad y la fauna requieren distancia, calma y un plan alterno.'],
        'MISION-COMUNIDAD-ACTIVA' => ['icon' => '🏘️', 'moving' => '👥', 'signal' => '📋', 'sky' => 'from-emerald-100 to-blue-100', 'accent' => 'bg-emerald-700', 'title' => 'Observar, proponer y transformar', 'description' => 'La evidencia y los acuerdos permiten mejorar el entorno sin exponer ni señalar personas.'],
        'MISION-TECNOLOGIA-MOVIL' => ['icon' => '📱', 'moving' => '🗺️', 'signal' => '🛡️', 'sky' => 'from-cyan-100 to-indigo-100', 'accent' => 'bg-cyan-700', 'title' => 'Tecnología bajo tu criterio', 'description' => 'La herramienta orienta, pero la realidad, la atención y la privacidad gobiernan la decisión.'],
        'MISION-SENALES-ACUERDOS' => ['icon' => '🚦', 'moving' => '🚗', 'signal' => '🛑', 'sky' => 'from-red-100 to-amber-100', 'accent' => 'bg-red-600', 'title' => 'Un lenguaje para coordinarnos', 'description' => 'Señales y prioridades reducen conflictos cuando se comprenden y se verifican en contexto.'],
        'MISION-MOVILIDAD-LABORAL' => ['icon' => '🦺', 'moving' => '🚐', 'signal' => '⏱️', 'sky' => 'from-amber-100 to-slate-200', 'accent' => 'bg-amber-700', 'title' => 'Trabajar con margen seguro', 'description' => 'La planificación, la autoridad para detenerse y el aprendizaje protegen frente a la presión laboral.'],
        'MISION-VEHICULO-SEGURO' => ['icon' => '🔧', 'moving' => '🚗', 'signal' => '🔍', 'sky' => 'from-slate-200 to-cyan-100', 'accent' => 'bg-slate-700', 'title' => 'Revisar antes de moverse', 'description' => 'Una condición detectada a tiempo permite detener el riesgo antes de entrar a la vía.'],
        'MISION-CRUCE-FERROVIARIO' => ['icon' => '🚂', 'moving' => '🚆', 'signal' => '✖️', 'sky' => 'from-red-100 to-slate-200', 'accent' => 'bg-red-700', 'title' => 'Nunca competir con el tren', 'description' => 'Esperar y confirmar la salida completa mantiene libre una trayectoria que no puede detenerse rápidamente.'],
        'MISION-AUTOCONTROL-VIAL' => ['icon' => '🧘', 'moving' => '💭', 'signal' => '⏸️', 'sky' => 'from-violet-100 to-rose-100', 'accent' => 'bg-violet-700', 'title' => 'Pausar antes de reaccionar', 'description' => 'Reconocer la emoción permite resistir presión, recuperar atención y salir del conflicto.'],
        'MISION-PUNTOS-CIEGOS' => ['icon' => '👁️', 'moving' => '🚛', 'signal' => '⚠️', 'sky' => 'from-orange-100 to-slate-200', 'accent' => 'bg-orange-700', 'title' => 'Ver el espacio que otra persona no ve', 'description' => 'La separación evita puntos ciegos, barridos de giro y distancias de detención insuficientes.'],
        'MISION-ESTACIONAMIENTO-SEGURO' => ['icon' => '🅿️', 'moving' => '🚙', 'signal' => '👧', 'sky' => 'from-blue-100 to-slate-100', 'accent' => 'bg-blue-700', 'title' => 'Baja velocidad, máxima atención', 'description' => 'Personas visibles, trayectoria libre y pausas convierten una maniobra en una acción controlada.'],
        'MISION-RUTA-DESCONOCIDA' => ['icon' => '🧭', 'moving' => '🚌', 'signal' => '🗺️', 'sky' => 'from-cyan-100 to-amber-100', 'accent' => 'bg-cyan-700', 'title' => 'Explorar con un plan seguro', 'description' => 'Investigar, orientarse y adaptarse permite conocer lugares nuevos sin improvisar la protección.'],
    ];
    $scene = $scenes[$module['code']] ?? ['icon' => '🧭', 'moving' => '●', 'signal' => '◇', 'sky' => 'from-slate-100 to-sky-100', 'accent' => 'bg-primary', 'title' => 'Misión de movilidad segura', 'description' => 'Observá, decidí con margen y explicá cómo protegerías la vida.'];
@endphp

@if ($module['code'] === 'MISION-CRUCE')
    <div class="mt-4">
        @include('courses.blocks.crossing-3d', ['sceneTitle' => 'Cruzar con información', 'sceneLabel' => 'Escena 3D del módulo'])
    </div>
@else
<section
    class="mt-4 overflow-hidden rounded-lg border border-border bg-surface"
    x-data="{ playing: true }"
    aria-label="Escena animada del módulo: {{ $scene['title'] }}"
>
    <div class="relative h-40 overflow-hidden bg-gradient-to-b {{ $scene['sky'] }}" role="img" aria-label="{{ $scene['description'] }}">
        <div class="absolute inset-x-0 bottom-0 h-8 bg-stone-300" aria-hidden="true"></div>
        <div class="absolute inset-x-0 bottom-8 h-16 bg-slate-600" aria-hidden="true">
            <div class="absolute left-0 top-1/2 w-full border-t-2 border-dashed border-amber-300"></div>
        </div>
        <span class="absolute left-5 top-5 text-4xl drop-shadow-sm" aria-hidden="true">{{ $scene['signal'] }}</span>
        <span class="absolute bottom-2 left-[18%] z-10 text-4xl drop-shadow-sm" aria-hidden="true">{{ $scene['icon'] }}</span>
        <span
            class="module-scene-actor absolute bottom-10 right-[12%] text-5xl drop-shadow-md motion-reduce:transition-none"
            :class="playing ? 'is-playing' : ''"
            aria-hidden="true"
        >{{ $scene['moving'] }}</span>
        <div class="absolute right-3 top-3 flex items-center gap-2 rounded-full bg-surface/95 px-3 py-2 shadow-sm">
            <span class="h-2.5 w-2.5 rounded-full {{ $scene['accent'] }}" :class="playing ? 'animate-pulse motion-reduce:animate-none' : ''" aria-hidden="true"></span>
            <span class="text-xs font-bold text-text">
                <span x-show="playing">Escena en movimiento</span>
                <span x-show="!playing" x-cloak>Escena pausada</span>
            </span>
        </div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div>
            <p class="font-heading font-bold text-text">{{ $scene['title'] }}</p>
            <p class="mt-1 max-w-2xl text-xs leading-5 text-text-secondary">{{ $scene['description'] }}</p>
        </div>
        <button
            type="button"
            class="min-h-11 rounded-md border border-primary px-4 text-sm font-bold text-primary hover:bg-primary/5 focus-visible:outline-none focus-visible:shadow-focus"
            @click="playing = !playing"
            :aria-pressed="(!playing).toString()"
        >
            <span x-show="playing">⏸ Pausar</span>
            <span x-show="!playing" x-cloak>▶ Reproducir</span>
        </button>
    </div>
</section>
@endif
