<x-layouts.app title="EDUDRIVE — Mi recorrido de cruce">
    <div class="mx-auto max-w-2xl space-y-6">
        <p class="rounded border border-warning p-4">Vista de estudiante en ensayo interno. Solo datos ficticios. Se conserva en esta sesión, no como evidencia académica; no habilitada para estudiantes reales.</p>
        <h1 class="text-2xl font-bold">Me detengo, observo y decido</h1>
        @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if(!$started)
            <h2 class="text-xl font-semibold">¿Qué vamos a hacer?</h2>
            <p>Imaginá que caminás con una persona adulta y querés llegar al otro lado de la calle. Todavía estás en la acera. Vas a leer qué ocurre y contar qué harías antes de cruzar y por qué.</p>
            <p>En otras situaciones, una barrera corta el camino por la acera: tendrás que pensar cómo continuar el recorrido. Cada situación te dirá qué quiere hacer el personaje.</p>
            <p>Tu tarea aquí es leer, decidir y responder con tus palabras. No vas a cruzar una calle real ni a mover un personaje en esta versión. Podés decir tu respuesta para que una persona adulta la escriba. No hay reloj ni competencia.</p>
            <form method="POST" action="{{ route('pilot-instruments.journey.start') }}" class="space-y-4">
                @csrf
                <label class="flex gap-3"><input type="checkbox" name="synthetic_only" value="1" required>Como responsable del ensayo, confirmo que usaré respuestas ficticias.</label>
                <button class="min-h-11 rounded bg-primary px-5 text-white">Comenzar recorrido</button>
            </form>
        @elseif(!$finished)
            <p>{{ $step['phase'] }} · Situación {{ $position + 1 }} de 9</p>
            <section class="space-y-4 rounded-lg border border-border bg-surface p-5" aria-labelledby="situation-title">
                <h2 id="situation-title" class="text-xl font-semibold">{{ $step['title'] }}</h2>
                @if(in_array($step['id'], ['D04', 'U01-C', 'C04'], true))
                    <p><strong>Qué querés hacer:</strong> seguir caminando por la acera con una persona adulta para llegar a tu destino. Una barrera interrumpe ese camino.</p>
                @else
                    <p><strong>Qué querés hacer:</strong> llegar al otro lado de la calle con una persona adulta. Todavía estás en la acera; aún no empezaste a cruzar.</p>
                @endif
                <p>{{ $step['scene'] }}</p>
                <p><strong>Tu tarea:</strong> imaginá que estás en esa situación y contá qué harías ahora y por qué. No tenés que salir a una calle real. Si la pregunta dice «mostrá» o «elegí», en esta pantalla podés explicarlo con palabras.</p>
                <p class="font-semibold">{{ $step['task'] }}</p>
                @if($history === [] || $step['phase'] === 'Aprendo y practico')
                    <form method="POST" action="{{ route('pilot-instruments.journey.update') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="revision" value="{{ $revision }}"><input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="action" value="answer">
                        <label class="block">Mi respuesta<textarea name="response" rows="4" maxlength="2000" class="mt-2 block w-full rounded border border-border p-3">{{ old('response') }}</textarea></label>
                        <p>Si todavía no sabés qué decir, podés guardar en blanco. Eso no dice cuánto valés ni significa que fallaste.</p>
                        <details><summary>Registro del acompañante: ayuda para decidir</summary><label class="block">Si diste una pista, anotala aquí. La lectura literal no es una pista.<input name="support" maxlength="500" class="mt-2 block w-full rounded border border-border p-3" value="{{ old('support') }}"></label></details>
                        <button class="min-h-11 rounded bg-primary px-5 text-white">{{ $history === [] ? 'Guardar mi respuesta' : 'Guardar otro intento' }}</button>
                    </form>
                @endif
                @if($step['phase'] === 'Aprendo y practico')
                    @if(isset($step['hint']))<p class="rounded border border-border p-3">Pista: {{ $step['hint'] }}</p>
                    @else
                        <form method="POST" action="{{ route('pilot-instruments.journey.update') }}">@csrf<input type="hidden" name="revision" value="{{ $revision }}"><input type="hidden" name="token" value="{{ $token }}"><button name="action" value="hint" class="min-h-11 rounded border border-border px-4">Necesito una pista</button></form>
                    @endif
                @endif
                @if(isset($step['explanation']))<aside class="rounded border border-border p-4"><h3 class="font-semibold">Pensemos en la decisión</h3><p>{{ $step['explanation'] }}</p></aside>@endif
            </section>
            @if($history !== [])
                <div role="status">Respuesta guardada. No se ha calificado.</div>
                <ol class="list-decimal space-y-2 pl-6" aria-label="Mis respuestas en esta situación">@foreach($history as $answer)<li>{{ $answer['response'] ?: 'Dejé mi respuesta en blanco.' }} @if($answer['formative'])<span>(Práctica o ayuda registrada)</span>@endif</li>@endforeach</ol>
                <form method="POST" action="{{ route('pilot-instruments.journey.update') }}">@csrf<input type="hidden" name="revision" value="{{ $revision }}"><input type="hidden" name="token" value="{{ $token }}"><button name="action" value="next" class="min-h-11 rounded bg-primary px-5 text-white">{{ $position === 8 ? 'Ver mi cierre' : 'Continuar' }}</button></form>
            @endif
            <p>Podés descansar y volver a esta página mientras siga abierta tu sesión. Este ensayo no reemplaza los encuentros separados de la guía docente.</p>
        @else
            <h2 class="text-xl font-semibold">Terminaste este recorrido de ensayo</h2>
            <p>Respondimos sobre dónde esperar, qué no podemos ver, qué movimientos comprobar y cuándo pedir ayuda. Terminar no significa que ya podás cruzar sin acompañamiento.</p>
            <h3 class="font-semibold">Revisemos tus ideas con una persona adulta</h3>
            <p>¿Qué cambiaste después de practicar? ¿En cuál situación necesitás más ayuda? Tu acompañante revisará lo que explicaste; esta pantalla no decide qué dominás.</p>
            <details class="rounded border border-border p-4"><summary>Ver mis respuestas y las ayudas registradas</summary>
                @foreach($summary as $attempts)
                    @foreach($attempts as $answer)
                        <p class="mt-3">{{ $answer['item'] }}: {{ $answer['response'] ?: 'Respuesta en blanco' }} · {{ $answer['formative'] ? 'Práctica o ayuda registrada' : 'Sin ayuda de contenido declarada' }}
                            @if($answer['hint_used'])
                                · Pista solicitada
                            @endif
                            @if($answer['support'])
                                · {{ $answer['support'] }}
                            @endif
                        </p>
                    @endforeach
                @endforeach
            </details>
            <p>No se emitió nota, certificado ni declaración de dominio. La transferencia protegida y el seguimiento posterior se realizan por separado.</p>
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.unit') }}">Volver a la guía del acompañante</a>
        @endif
    </div>
</x-layouts.app>
