<x-layouts.app title="EDUDRIVE — Cruzar con acompañamiento">
    <style>
        .dc{color:#153e33;background:#f5f6ef;border-radius:24px;padding:clamp(18px,3vw,36px);font-family:inherit;color-scheme:light}
        .dc *{box-sizing:border-box}.dc h1{font-size:clamp(28px,3.6vw,44px);line-height:1.16;font-weight:750;letter-spacing:-.7px;margin:12px 0 16px;max-width:22ch}.dc h2{font-size:22px;line-height:1.3;font-weight:700;margin-bottom:10px}.dc p{line-height:1.6}
        .dc-nav{display:flex;gap:8px 22px;flex-wrap:wrap;border-bottom:1px solid #d7e1d5;padding-bottom:14px;margin-bottom:18px}.dc-nav a{display:inline-flex;align-items:center;font-size:14px;text-decoration:underline;text-underline-offset:4px;min-height:44px}.dc-tag{font-size:12px;letter-spacing:.4px;color:#476757}
        .dc-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:28px;align-items:center;margin:24px 0}.dc-grid>*{min-width:0}
        .dc-button{display:inline-flex;align-items:center;justify-content:center;gap:14px;background:#214e3b;color:#fffdf5;border:2px solid #214e3b;border-radius:16px;padding:14px 20px;min-height:56px;font-weight:700;font-size:18px;line-height:1.4;text-align:left;cursor:pointer;text-decoration:none}.dc-button:hover{background:#346248}.dc-button:active{transform:translateY(1px)}
        .dc-button:focus-visible,.dc a:focus-visible,.dc summary:focus-visible{outline:3px solid #9c6800;outline-offset:4px}.dc-choice{width:100%;justify-content:flex-start;background:#fffdf6;color:#153e33;border-color:#cbd9c7;padding:12px;min-height:100px}.dc-choice:hover{background:#edf1e4;border-color:#476757}.dc-choice-picture{width:96px;height:72px;flex:none}.dc-choice-label{flex:1}.dc-choice-arrow{font-size:24px;padding:0 4px}
        .dc-options{display:grid;gap:14px}.dc-actions{display:flex;gap:14px;flex-wrap:wrap;align-items:center;margin-top:22px}.dc-panel{border-radius:18px;background:#fffdf6;border:1px solid #d7e1d5;padding:22px;margin-top:22px}.dc-feedback{background:#e5efda;border-left:5px solid #476b43}.dc-retry{background:#fff0cf;border-left:5px solid #9b731d}.dc-note{font-size:14px;color:#52695d;margin-top:18px}.dc-list{padding-left:24px;list-style:decimal}.dc-list li{margin:12px 0}.dc-pending{display:inline-block;background:#e3ecdc;border-radius:20px;padding:7px 13px;font-size:14px;margin-bottom:12px}
        .dc-scene{margin:0;border-radius:20px;overflow:hidden;background:#fffdf6;border:1px solid #d7e1d5}.dc-scene svg{display:block;width:100%;height:auto}.dc-scene figcaption{padding:14px 18px;font-size:15px;line-height:1.5;border-top:1px solid #d7e1d5}
        .dc-foot{border-top:1px solid #d7e1d5;margin-top:28px;padding-top:16px;font-size:13px;color:#52695d}.dc-progress{height:7px;background:#d7e1d5;border-radius:6px;overflow:hidden;margin:12px 0}.dc-progress span{display:block;height:100%;background:#214e3b}.dc-current{padding:14px;background:#e3ecdc;border-radius:12px;font-weight:700}.dc summary{cursor:pointer;padding:12px 0;min-height:44px}.dc a:not(.dc-button){color:inherit}
        .dc-route{display:flex;gap:8px;list-style:none;padding:0;margin:20px 0 26px}.dc-route li{display:flex;gap:8px;align-items:center;flex:1;border-bottom:3px solid #d7e1d5;padding:0 0 12px;font-size:14px;color:#52695d}.dc-route li[aria-current=step]{border-color:#214e3b;color:#153e33;font-weight:700}.dc-route-number{display:grid;place-items:center;width:28px;height:28px;flex:none;border-radius:50%;background:#e3ecdc}.dc-route li[aria-current=step] .dc-route-number{background:#214e3b;color:#fffdf6}
        .dc-selected{display:flex;gap:14px;align-items:center;margin-top:0}.dc-selected p{font-size:14px;margin-bottom:4px}.dc-instruction{font-weight:650;font-size:17px;margin:18px 0 0}.dc-companion{font-size:14px;color:#52695d}
        @media(max-width:760px){.dc-grid{grid-template-columns:1fr;gap:20px}.dc h1{max-width:none}.dc-nav{gap:4px 16px}.dc-actions{align-items:stretch}.dc-actions form,.dc-actions .dc-button{width:100%}.dc-route{gap:8px}.dc-route li{font-size:13px;gap:6px}.dc-panel{padding:18px}}
        @media(max-width:380px){.dc-choice-picture{width:72px;height:54px}.dc-choice{gap:10px;font-size:16px}.dc-route-number{width:23px;height:23px}.dc-route li{font-size:12px}.dc-route{gap:5px}}
        @media(prefers-reduced-motion:reduce){.dc-button:active{transform:none}}
        .dc-audio{border:1px solid #d7e1d5;border-radius:16px;background:#fffdf6;padding:14px 16px;margin:18px 0 24px}.dc-audio[hidden]{display:none!important}.dc-audio-actions{display:flex;gap:10px;flex-wrap:wrap}.dc-audio-listen{font-size:16px;min-height:48px;padding:10px 16px}.dc-audio-stop{background:#fffdf6;color:#214e3b;font-size:16px;min-height:48px;padding:10px 16px}.dc-audio-stop:hover{background:#e7eddf}.dc-audio .dc-button:disabled{opacity:.55;cursor:default;transform:none}.dc-audio-status{font-size:13px;color:#52695d;margin-top:10px;line-height:1.5}
    </style>
    <div class="dc" data-dc-reader>
        <nav class="dc-nav" aria-label="Recorrido DESCUBRO">
            <a href="{{ route('student-profile.show') }}">← Mi perfil</a>
            <a href="{{ route('descubro.crossing.show', ['page' => 'home']) }}">Mi ruta DESCUBRO</a>
            <a href="{{ route('road-passport.show') }}">Mi Pasaporte Vial</a>
            <a href="{{ route('descubro.crossing.show', ['page' => 'adult']) }}">Para acompañantes</a>
            <a href="{{ route('descubro.crossing.show', ['page' => 'review'], false) }}" @if($page === 'review') aria-current="page" @endif>Repasar lo aprendido</a>
        </nav>
        <p class="dc-tag">DESCUBRO · 3–6 años · Vista de revisión</p>
        @if ($errors->any())<div class="dc-panel dc-retry" role="alert">Revisá tu elección e intentá de nuevo.</div>@endif

        @if (in_array($page, ['home', 'learn', 'practice', 'result'], true))
            @php($routeStep = ['home' => 0, 'learn' => 0, 'practice' => 1, 'result' => 2][$page])
            <ol class="dc-route" aria-label="Etapas del recorrido">
                @foreach (['Aprendo', 'Practico', 'Mi progreso'] as $stepIndex => $stepLabel)
                    <li @if($stepIndex === $routeStep) aria-current="step" @endif><span class="dc-route-number" aria-hidden="true">{{ $stepIndex + 1 }}</span>{{ $stepLabel }}</li>
                @endforeach
            </ol>
        @endif

        @include('descubro.audio')

        @if ($page === 'home')
            <div class="dc-grid">
                <section><h1 data-dc-read>Cruzar con acompañamiento.</h1><p data-dc-read>Vamos al parque con Tito. Aprendemos dónde caminar, cuándo esperar y cómo cruzar juntos.</p>
                    <div class="dc-actions">
                        @if ($run['phase'] === 'home')
                            @include('descubro.action', ['action' => 'start', 'label' => 'Empezar juntos →'])
                        @else
                            <a class="dc-button" href="{{ route('descubro.crossing.show') }}">Continuar mi recorrido →</a>
                        @endif
                    </div>
                </section>
                @include('descubro.scene', ['scene' => 'bus'])
            </div>
            @if ($run['phase'] !== 'home')
                <div class="dc-panel"><p><strong>{{ $practiceSummary['phase'] }}</strong></p>@include('descubro.skills')<p class="dc-note"><strong>Siguiente paso:</strong> {{ $practiceSummary['next'] }}</p></div>
            @endif
            <div class="dc-panel"><h2>Aprendo → Practico → Mi progreso</h2><p data-dc-read>La persona adulta puede leer y acompañar. Podés responder señalando; no hay reloj ni carreras.</p></div>
        @elseif ($page === 'learn')
            <h1 data-dc-read>Primero, lo hacemos juntos.</h1>
            <p>Paso {{ $run['lesson'] + 1 }} de 3 · La persona adulta lee una consigna por vez.</p>
            <div class="dc-grid"><div><ol class="dc-list">@foreach ($lessons as $i => $item)<li @if($i === $run['lesson']) aria-current="step" @endif @class(['dc-current' => $i === $run['lesson']])>{{ $item['title'] }}</li>@endforeach</ol></div><div>@include('descubro.scene', ['scene' => $lesson['scene']])</div></div>
            <div class="dc-panel dc-feedback"><h2 data-dc-read>{{ $lesson['title'] }}</h2><p data-dc-read>{{ $lesson['text'] }}</p></div>
            <div class="dc-actions">@include('descubro.action', ['action' => $run['lesson'] < 2 ? 'lesson-next' : 'practice-start', 'label' => $run['lesson'] < 2 ? 'Siguiente paso →' : 'Vamos a practicar →'])</div>
        @elseif ($page === 'practice')
            <p class="dc-note">Decisión {{ $run['question'] + 1 }} de 4 · Práctica con retroalimentación</p>
            <div class="dc-progress" aria-hidden="true"><span style="width:{{ ($run['question'] + ($run['feedback'] === 'success' ? 1 : 0)) * 25 }}%"></span></div>
            <h1 data-dc-read>{{ $question['title'] }}</h1><p data-dc-read>{{ $question['prompt'] }}</p>
            @if ($run['feedback'] === null)<p class="dc-instruction">Mirá la escena. Tocá o señalá tu elección.</p>@endif
            <div class="dc-grid">
                @include('descubro.scene', ['scene' => $run['question'] === 3 && $run['feedback'] === 'success' ? 'arrived' : ($run['question'] === 3 ? 'ready' : $question['scene'])])
                <div class="dc-options">
                    @if ($run['feedback'] === null)
                        @foreach ($question['choices'] as $i => $option)@include('descubro.action', ['action' => 'answer', 'choice' => $i, 'label' => $option])@endforeach
                    @else
                        <div class="dc-panel dc-selected">@include('descubro.choice-picture', ['choice' => $run['choice']])<div><strong data-dc-read>Elegiste: {{ $question['choices'][$run['choice']] }}</strong></div></div>
                    @endif
                </div>
            </div>
            @if ($feedback !== null)
                <section @class(['dc-panel', 'dc-feedback', 'dc-retry' => $run['feedback'] === 'retry']) role="status" aria-atomic="true">
                    <h2>{{ $run['feedback'] === 'success' ? 'Lo practicamos juntos.' : 'Hacemos una pausa.' }}</h2><p data-dc-read>{{ $feedback }}</p>
                    <div class="dc-actions">@include('descubro.action', ['action' => $run['feedback'] === 'retry' ? 'retry' : 'next', 'label' => $run['feedback'] === 'retry' ? 'Volver a intentar' : ($run['question'] === 3 ? 'Ver lo que practiqué →' : 'Continuar →')])</div>
                </section>
            @else
                <p class="dc-note">Podés señalar el dibujo y pedir a tu acompañante que toque la opción. Recibir ayuda y volver a intentar también es aprender.</p>
            @endif
        @elseif ($page === 'result')
            <div class="dc-grid"><section><span class="dc-pending">Práctica digital completada</span><h1 data-dc-read>Un camino que hacemos juntos.</h1><p data-dc-read>Practicaste reconocer la acera, esperar, volver a comprobar y conservar la compañía.</p></section>@include('descubro.scene', ['scene' => 'arrived'])</div>
            <div class="dc-panel">@include('descubro.skills')</div>
            <div class="dc-panel"><h2>La habilidad sigue en desarrollo.</h2><p data-dc-read>Demostración en circuito protegido: pendiente de observación. Esta práctica digital no acredita la habilidad corporal.</p></div>
            @php($reinforce = collect($run['attempts'])->where('correct', false)->pluck('skill')->unique())
            @if ($reinforce->isNotEmpty())
                <div class="dc-panel"><h2>Conviene seguir practicando</h2><ul class="dc-list">@foreach ($reinforce as $skill)<li>{{ ['S1' => 'Reconocer el espacio para caminar.', 'S2' => 'Detenerse y comprobar antes de entrar.', 'S3' => 'Conservar la compañía hasta llegar.'][$skill] }}</li>@endforeach</ul></div>
            @endif
            <div class="dc-actions"><a class="dc-button" href="{{ route('road-passport.show') }}">Ver mi Pasaporte Vial →</a>@include('descubro.action', ['action' => 'practice-start', 'label' => 'Volver a practicar'])</div>
            <div class="dc-actions"><a class="dc-button" href="{{ route('descubro.crossing.show', ['page' => 'review'], false) }}">Repasar las tres explicaciones →</a><a href="{{ route('descubro.crossing.show', ['page' => 'circuit']) }}">Ver el siguiente paso acompañado →</a></div>
        @elseif ($page === 'review')
            @include('descubro.review')
        @elseif ($page === 'adult')
            <h1 data-dc-read>Tu compañía es parte del aprendizaje.</h1><p data-dc-read>Leé una consigna por vez. Dejá espacio para señalar, hablar o usar gestos.</p>
            <div class="dc-panel"><ol class="dc-list" data-dc-read><li>Dejá que elija primero, sin pedir rapidez.</li><li>Conversen sobre la decisión. Durante la práctica podés explicar y ayudar.</li><li>Paren cuando lo necesiten. El avance se guarda en tu cuenta, incluso si cerrás sesión.</li></ol></div>
            <div class="dc-panel"><h2>Qué muestra el Pasaporte</h2><p>Una práctica en pantalla queda separada de una habilidad demostrada. Para revisar la habilidad corporal se necesita observación con instrumentos y criterios revisados.</p></div>
            <div class="dc-actions"><a class="dc-button" href="{{ route('descubro.crossing.show') }}">Volver al recorrido →</a><a href="{{ route('descubro.crossing.show', ['page' => 'circuit']) }}">Ver actividad acompañada</a></div>
        @else
            <h1 data-dc-read>Del dibujo al recorrido.</h1>
            <p data-dc-read>Guía para la persona adulta: preparar una calle de juego en una sala o patio cerrado al tránsito y practicar juntos, al ritmo de la niña o el niño.</p>
            <div class="dc-panel">
                <h2 data-dc-read>Antes de empezar</h2>
                <ul class="dc-list" data-dc-read>
                    <li>Elegí un espacio separado del tránsito, sin acceso de vehículos durante la actividad. Revisá que el piso y el recorrido estén despejados.</li>
                    <li>Marcá dos aceras y una calzada con cinta bien fijada o dibujos. Usá dibujos de vehículos o juguetes adecuados para la edad, fuera del paso.</li>
                    <li>Acordá cómo se comunicarán: palabras, gestos o imágenes. Ajustá el espacio y el acompañamiento a sus necesidades de movilidad y comunicación.</li>
                </ul>
                <p class="dc-note" data-dc-read>La actividad se representa en un espacio protegido. No se realiza en una calle abierta al tránsito.</p>
            </div>
            <div class="dc-panel">
                <h2 data-dc-read>Tres pasos para hacer juntos</h2>
                <ol class="dc-list" data-dc-read>
                    <li><strong>Reconocemos el espacio.</strong> Preguntá: «¿Dónde caminamos?». Dejá que señale la acera y la calzada. Recorran juntos la acera representada.</li>
                    <li><strong>Nos detenemos y comprobamos.</strong> Antes del borde, hacé una pausa. Preguntá: «¿Qué necesitamos comprobar?». Miren de dónde podrían acercarse vehículos; escuchen cuando sea posible. Si algo no está claro, esperen juntos.</li>
                    <li><strong>Cruzamos con compañía.</strong> Después de comprobar el entorno representado, indicá cuándo pueden avanzar. Recorran juntos hasta la otra acera, conservando la compañía hasta llegar.</li>
                </ol>
                <p class="dc-note" data-dc-read>Podés mostrar el paso, explicarlo otra vez y ayudar. Si hay cansancio, incomodidad o confusión, hagan una pausa. No hay reloj ni carreras.</p>
            </div>
            <div class="dc-panel">
                <h2 data-dc-read>Qué observar al acompañar</h2>
                <ul class="dc-list" data-dc-read>
                    <li><strong>Reconocer:</strong> distingue la acera de la calzada, señalando, hablando o usando imágenes.</li>
                    <li><strong>Detenerse y comprobar:</strong> hace la pausa antes de entrar y participa en la comprobación contigo.</li>
                    <li><strong>Cruzar juntos:</strong> espera tu indicación y conserva la compañía hasta la otra acera.</li>
                </ul>
                <p data-dc-read>Conversen sobre lo que salió y lo que conviene repetir. Señalar una respuesta y realizar el movimiento son observaciones diferentes; la ayuda que necesitó también importa.</p>
            </div>
            <div class="dc-panel">
                <h2 data-dc-read>Otra situación para conversar</h2>
                <p data-dc-read>En el mismo espacio protegido, agregá un dibujo de una entrada de garaje. Preguntá: «¿Qué cambia aquí?». Practiquen de nuevo la pausa y la comprobación con acompañamiento.</p>
                <p data-dc-read>También pueden colocar el dibujo de una pelota en la calzada representada. Preguntá: «¿Qué hacemos?». La respuesta que practicamos es quedarse con la persona adulta; no salir detrás de la pelota.</p>
            </div>
            <div class="dc-panel">
                <h2 data-dc-read>Observación pendiente</h2>
                <p data-dc-read>Esta guía está en revisión. La matriz propone dos observaciones logradas en días y contextos protegidos distintos, con instrumentos y criterios revisados. Esta pantalla no registra una evaluación ni acredita dominio.</p>
                <p class="dc-note">Antes de aplicarla con participantes, falta cerrar la revisión curricular y organizar la observación. Consultar la guía conserva tu avance digital y no cambia el Pasaporte.</p>
            </div>
            <div class="dc-actions"><a class="dc-button" href="{{ route('descubro.crossing.show') }}">Volver a mi recorrido →</a><a href="{{ route('descubro.crossing.show', ['page' => 'review'], false) }}">Repasar las explicaciones</a><a href="{{ route('road-passport.show') }}">Ver mi Pasaporte Vial</a></div>
        @endif
        <footer class="dc-foot">Avance guardado en tu cuenta. Podés cerrar sesión y continuar después desde el mismo paso. Esta práctica no emite evidencias de dominio, certificados ni puntos.</footer>
    </div>
    <script type="module" src="{{ asset('js/descubro-read-aloud.js') }}?v=1"></script>
</x-layouts.app>
