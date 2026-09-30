<x-layouts.app title="EDUDRIVE — Ensayo de instrumentos P912">
    <div class="mx-auto max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold">Piloto 9–12 · Ensayo de instrumentos</h1>
        <p class="rounded-lg border border-warning p-4">Borrador para ensayo interno. Usá únicamente respuestas ficticias, sin nombres ni datos de estudiantes. No genera notas, progreso, evidencias del Pasaporte ni certificados.</p>
        @if($errors->any())
            <div role="alert" class="rounded-lg border border-warning p-4"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if($run === null)
            <a class="inline-block rounded border border-border p-3 underline" href="{{ route('pilot-instruments.unit') }}">Consultar la unidad modelo (guía docente, no mostrar antes del diagnóstico)</a>
            <form method="POST" action="{{ route('pilot-instruments.start') }}" class="space-y-5 rounded-lg border border-border bg-surface p-5">
                @csrf
                <label class="block">Instrumento
                    <select name="form" class="mt-2 block w-full rounded border border-border p-3">
                        <option value="diagnostic">Diagnóstico inicial · 4 situaciones</option>
                        <option value="practice">Práctica con ayuda · 2 situaciones</option>
                        <option value="independent">Comprobación independiente · 4 situaciones</option>
                        <option value="retention">Seguimiento · 2 situaciones</option>
                    </select>
                </label>
                <label class="flex gap-3"><input type="checkbox" name="synthetic_only" value="1" required>Confirmo que usaré datos ficticios para ensayar el instrumento.</label>
                <button class="min-h-11 rounded bg-primary px-5 text-white">Iniciar ensayo</button>
            </form>
        @else
            <p>Forma: {{ ['diagnostic'=>'Diagnóstico','practice'=>'Práctica','independent'=>'Comprobación independiente','retention'=>'Seguimiento'][$run['form']] }} · Versión {{ $run['version'] }} · {{ $run['status'] === 'closed' ? 'Cerrada' : 'Abierta' }}</p>
            <p>No se exige una respuesta correcta. Podés registrar una respuesta en blanco o cerrar con ítems pendientes. Las ayudas de contenido se distinguen de los apoyos de acceso.</p>
            @if($run['status'] === 'closed')
                <div role="status" class="rounded-lg border border-border p-4">Ensayo cerrado. La revisión corresponde a un facilitador; no hay calificación automática. Los ítems sin registro no se interpretan como errores. <a class="underline" href="{{ route('pilot-instruments.index') }}">Iniciar otro ensayo</a></div>
            @endif
            @foreach($run['items'] as $item)
                <section class="space-y-3 rounded-lg border border-border bg-surface p-5" aria-labelledby="title-{{ $item['id'] }}">
                    <h2 id="title-{{ $item['id'] }}" class="text-lg font-bold">{{ $item['id'] }} · {{ $item['title'] }}</h2>
                    <p>{{ $item['scene'] }}</p><p class="font-semibold">{{ $item['task'] }}</p>
                    @if($run['status'] === 'open')
                        <form method="POST" action="{{ route('pilot-instruments.update', $run['id']) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="revision" value="{{ $run['revision'] }}">
                            <input type="hidden" name="item_id" value="{{ $item['id'] }}">
                            <input type="hidden" name="action" value="response">
                            <label class="block">Respuesta ficticia o transcripción neutral (puede quedar vacía)<textarea name="response" maxlength="2000" rows="3" class="mt-1 block w-full rounded border border-border p-2"></textarea></label>
                            <label class="block">Apoyo de acceso, por ejemplo lectura literal<input name="access_support" maxlength="500" class="mt-1 block w-full rounded border border-border p-2"></label>
                            <label class="block">Ayuda de contenido dada por el facilitador, si existió<input name="content_help" maxlength="500" class="mt-1 block w-full rounded border border-border p-2"></label>
                            <p class="text-sm">Una respuesta después de una pista no se considera independiente. Guardar de nuevo añade un registro; no borra la primera respuesta.</p>
                            <button class="min-h-11 rounded bg-primary px-4 text-white">Guardar respuesta</button>
                        </form>
                        @if($item['can_hint'])
                            <form method="POST" action="{{ route('pilot-instruments.update', $run['id']) }}">
                                @csrf
                                <input type="hidden" name="revision" value="{{ $run['revision'] }}"><input type="hidden" name="item_id" value="{{ $item['id'] }}"><input type="hidden" name="action" value="hint">
                                <button class="min-h-11 rounded border border-border px-4">Pedir siguiente pista y registrarla</button>
                            </form>
                        @endif
                    @endif
                    @if(isset($item['feedback']))<p class="rounded bg-background p-3">{{ $item['feedback'] }}</p>@endif
                    <ol class="space-y-2 text-sm" aria-label="Historial de este ítem">
                        @foreach($run['events'] as $event)
                            @if(($event['item_id'] ?? null) === $item['id'])
                                <li class="rounded bg-background p-3">
                                    @if($event['type'] === 'hint') Pista solicitada: {{ $event['text'] }}
                                    @else
                                        <p>Respuesta registrada: {{ $event['response'] === '' ? 'Sin respuesta escrita' : $event['response'] }}</p>
                                        <p>Apoyo de acceso: {{ $event['access_support'] ?: 'No declarado' }}</p>
                                        <p>Ayuda de contenido: {{ $event['content_help'] ?: 'No declarada' }}</p>
                                        <p>Contexto: {{ $event['response_context'] === 'formative' ? 'Formativo: práctica o ayuda de contenido registrada' : 'Sin ayuda de contenido declarada; no equivale a dominio demostrado' }}</p>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </section>
            @endforeach
            @if($run['status'] === 'open')
                <form method="POST" action="{{ route('pilot-instruments.update', $run['id']) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="revision" value="{{ $run['revision'] }}"><input type="hidden" name="action" value="finish">
                    <p>Al cerrar se conserva lo registrado y no se podrán añadir respuestas. Los ítems pendientes quedarán identificados.</p>
                    <button class="min-h-11 rounded bg-primary px-5 text-white">Cerrar ensayo, aunque haya ítems pendientes</button>
                </form>
            @else
                @php($closing = collect($run['events'])->last())
                <p>Ítems sin registro: {{ implode(', ', $closing['unanswered_items'] ?? []) ?: 'Ninguno' }}. Una respuesta guardada en blanco se distingue de un ítem sin registro; este ensayo no verifica si la consigna fue presentada.</p>
            @endif
        @endif
    </div>
</x-layouts.app>
