@php
    // Textos de cada escena 3D de respuesta ante incidentes. Las claves de pasos son los ids de las opciones.
    $catalog = [
        'fallen-bicycle' => [
            'intro' => 'Abrí la escena 3D para observar qué pasa alrededor de la persona caída mientras el tránsito sigue circulando.',
            'legend' => 'Verde: lugar protegido y acceso libre para la ayuda. Rojo: exposición que aumenta el peligro. Amarillo: persona que necesita ayuda.',
            'steps' => [
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
            ],
        ],
        'unknown-cable' => [
            'intro' => 'Abrí la escena 3D para observar el cable caído, la zona de peligro y qué pasa según tu decisión.',
            'legend' => 'Anillo amarillo o rojo: zona de peligro alrededor del cable, que no se debe pisar. Verde: lugar seguro desde donde advertir y pedir ayuda. Azul: posible descarga eléctrica.',
            'steps' => [
                'segura' => [
                    'Te detenés lejos del cable, fuera de la zona de peligro.',
                    'Advertís a otras personas con un gesto y una señal, sin acercarte ni tocar el cable.',
                    'Pedís ayuda a emergencias y a la empresa eléctrica; nadie se acerca al cable.',
                ],
                'exponer' => [
                    'Pensás en mover el cable para despejar el paso.',
                    'Te acercás: un cable caído puede estar energizado aunque no se vea nada.',
                    'Tocarlo o acercarte crea una segunda víctima: el peligro aumentó.',
                ],
                'grabar' => [
                    'Sacás el teléfono para grabar y compartir.',
                    'Mientras grabás, otra persona y un vehículo se acercan sin saber del peligro.',
                    'Primero se advierte y se pide ayuda; después, si es seguro, se comparte.',
                ],
            ],
        ],
        'incomplete-message' => [
            'intro' => 'Abrí la escena 3D para observar qué pasa cuando un aviso llega incompleto y cada decisión que podés tomar.',
            'legend' => 'Gris: ubicación sin confirmar. Verde: ubicación y datos confirmados. Azul: servicio de ayuda y preguntas necesarias. Violeta: información que circula en grupos o redes.',
            'steps' => [
                'segura' => [
                    'Alguien dice «hubo un accidente» y corta. Te quedás disponible, con el teléfono cerca.',
                    'El servicio de ayuda pregunta y respondés solo lo necesario: dónde ocurrió y si hay personas heridas.',
                    'Con esos datos confirmados, la ayuda llega al lugar correcto.',
                ],
                'exponer' => [
                    'Recibís el aviso incompleto y querés avisar a más personas.',
                    'Enviás el mensaje a un grupo y esperás: nadie sabe dónde ocurrió ni qué hacer.',
                    'Se pierde tiempo y circula información sin confirmar; la ayuda no llega.',
                ],
                'grabar' => [
                    'Querés mostrar lo que pasa y empezás a grabar.',
                    'Mientras grabás, nadie llama al servicio de ayuda.',
                    'Compartir imágenes retrasa la respuesta y vulnera la privacidad: primero se pide ayuda.',
                ],
            ],
        ],
        'private-location' => [
            'intro' => 'Abrí la escena 3D para observar cómo pedir ayuda desde un lugar público sin exponer tu ubicación ni tus fotos.',
            'legend' => 'Azul: servicio de ayuda y preguntas necesarias. Verde: referencia mínima y acompañamiento adulto. Violeta: redes públicas que cualquiera puede ver. Rojo: privacidad expuesta.',
            'steps' => [
                'segura' => [
                    'Necesitás pedir ayuda en un lugar público. Un adulto de confianza se acerca para acompañarte.',
                    'Llaman al servicio de ayuda y dan solo la referencia necesaria: la parada de bus frente a la escuela.',
                    'La ayuda llega al lugar correcto y esperás acompañada, sin exponer tu ubicación a desconocidos.',
                ],
                'exponer' => [
                    'Pensás en publicar tu ubicación y tus fotos para que alguien te ayude.',
                    'La publicación la puede ver cualquier persona: nadie sabe quién la va a leer.',
                    'Tu ubicación y tus fotos quedan expuestas y nadie llamó a un servicio de ayuda.',
                ],
                'grabar' => [
                    'Querés grabar lo que pasa y compartirlo.',
                    'Mientras grabás y compartís, nadie llama al servicio de ayuda.',
                    'Compartir imágenes retrasa la ayuda y vulnera la privacidad: primero se pide ayuda.',
                ],
            ],
        ],
    ];
    $config = $catalog[$sceneKey];
@endphp
<section class="rounded-lg border-2 border-primary bg-surface p-5" x-data="incidentDecision3d(@js($scenario['choices']), @js($block['id']), @js($sceneKey))" aria-labelledby="scenario-{{ $block['id'] }}">
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Práctica de decisión · 3D</p>
    <h4 id="scenario-{{ $block['id'] }}" class="mt-1 font-heading text-lg font-bold">{{ $scenario['title'] }}</h4>
    <div class="mt-4 overflow-hidden rounded-lg border border-border bg-background">
        <div x-ref="viewport" style="height:clamp(320px,48vw,500px);position:relative;background:#c8e4ef">
            <div x-show="!ready" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center" style="color:#12324a">
                <p class="max-w-lg text-sm" x-text="error || @js($config['intro'])"></p>
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
            @foreach ($config['steps'] as $id => $texts)
                <template x-if="selected?.id === @js($id)">
                    <p class="text-sm font-medium leading-6 text-text" x-text="@js($texts)[step]"></p>
                </template>
            @endforeach
        </div>
    </div>
    <p class="mt-4 font-semibold text-text">{{ $scenario['prompt'] }}</p>

    @include('courses.blocks.editorial-decision-feedback', ['answerField' => $answerField ?? null])

    <p class="mt-4 text-xs text-text-secondary">{{ $config['legend'] }}</p>
    <details class="mt-4 text-sm text-text-secondary">
        <summary class="cursor-pointer font-medium">Alternativa accesible</summary>
        <p class="mt-2 leading-6">{{ $scenario['accessible_text'] }}</p>
    </details>
</section>
