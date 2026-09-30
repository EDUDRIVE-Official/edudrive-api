<x-layouts.app title="EDUDRIVE — Revisión de escenas">
    @php
        $scenes = [
            ['key' => 'van', 'name' => 'La van que bloquea la vista', 'route' => 'pilot-instruments.van', 'focus' => 'Visibilidad y permanencia en la acera.'],
            ['key' => 'turn', 'name' => 'El carro que gira', 'route' => 'pilot-instruments.visual', 'focus' => 'Conflicto entre señal favorable y trayectoria de giro.'],
            ['key' => 'barrier', 'name' => 'Ruta interrumpida', 'route' => 'pilot-instruments.barrier', 'focus' => 'Cambio de plan ante una acera totalmente bloqueada.'],
            ['key' => 'descent', 'name' => 'Cambió el lugar de descenso', 'route' => 'pilot-instruments.descent', 'focus' => 'Comunicación antes de bajar a un espacio sin acera.'],
        ];
        $criteria = [
            'road_fidelity' => ['Fidelidad vial', 'Geometría, carriles, aceras, señales y trayectorias son coherentes.'],
            'decision_clarity' => ['Decisión comprensible', 'La escena inicial y las opciones representan exactamente lo que se pregunta.'],
            'responsible_outcome' => ['Consecuencia responsable', 'No hay choque, sobresalto, culpa al niño ni permiso de cruce injustificado.'],
            'equivalent_access' => ['Acceso equivalente', 'Teclado, texto alternativo, movimiento reducido y lenguaje funcionan sin depender del color.'],
        ];
        $specialties = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'];
    @endphp
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm">Registro interno · No constituye aprobación</p>
            <h1 class="text-3xl font-bold">Revisión de calidad de las escenas</h1>
            <p>Versión revisada: <strong>{{ $sceneVersion }}</strong> · Revisiones guardadas por esta cuenta: <strong>{{ count($reviews) }} de 4</strong>.</p>
            <div class="flex flex-wrap gap-3">
                <button type="button" @click="window.print()" class="min-h-11 rounded border border-border px-4">Imprimir hoja</button>
                <a class="inline-block min-h-11 rounded border border-border px-4 py-3 font-semibold" href="{{ route('pilot-instruments.visual-review-guide') }}">Consultar guía de revisión</a>
                <a class="inline-block min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" href="{{ route('pilot-instruments.visual-sequence') }}">Volver al recorrido</a>
                <a class="inline-block min-h-11 rounded border border-border px-4 py-3 font-semibold" href="{{ route('pilot-instruments.visual-review-summary') }}">Ver cobertura de especialidades</a>
            </div>
        </header>

        @if(session('status'))<p role="status" class="rounded-lg border border-green-500 bg-green-50 p-4 text-green-950">{{ session('status') }}</p>@endif
        @if($errors->any())<div role="alert" class="rounded-lg border border-red-500 bg-red-50 p-4 text-red-950"><strong>No se guardó la revisión.</strong><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <aside class="rounded-lg border border-amber-400 bg-amber-50 p-4 text-amber-950">
            <strong>Alcance limitado.</strong> Cada registro pertenece a la cuenta autenticada y a esta versión de escena. “Lista para revisión especializada” no significa aprobada; la aprobación formal sigue fuera de este prototipo.
        </aside>

        @if($designation === null)
            <aside class="rounded-lg border border-red-400 bg-red-50 p-4 text-red-950"><strong>No tenés una designación activa.</strong> Un responsable con permiso para gestionar roles debe registrar tu especialidad y la referencia de respaldo antes de que podás enviar revisiones.</aside>
        @else
            <aside class="rounded-lg border border-blue-400 bg-blue-50 p-4 text-blue-950"><strong>Designación activa:</strong> {{ $specialties[$designation['specialty']] }} · {{ $designation['organization'] }} · referencia {{ $designation['evidence_reference'] }}. La designación interna no certifica por sí sola las credenciales profesionales.</aside>
        <div class="space-y-5" aria-label="Registros internos de revisión">
            @foreach($scenes as $index => $scene)
                @php $review = $reviews[$scene['key']] ?? null; @endphp
                <form method="POST" action="{{ route('pilot-instruments.visual-review.store') }}" class="space-y-4 rounded-xl border border-border bg-surface p-5">
                    @csrf
                    <input type="hidden" name="scene" value="{{ $scene['key'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="text-xl font-bold">{{ $index + 1 }}. {{ $scene['name'] }}</h2><p>{{ $scene['focus'] }}</p></div>
                        <span class="rounded-full px-3 py-1 text-sm {{ $review ? 'bg-green-100 text-green-900' : 'bg-slate-100 text-slate-700' }}">{{ $review ? 'Registro guardado' : 'Sin revisar' }}</span>
                    </div>
                    <a target="_blank" rel="noopener" class="inline-block min-h-11 rounded border border-border px-4 py-3 underline" href="{{ route($scene['route']) }}">Abrir escena en otra pestaña</a>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach($criteria as $key => [$label, $description])
                            <label class="flex gap-3 rounded border border-border p-3">
                                <input type="hidden" name="criteria[{{ $key }}]" value="0">
                                <input class="mt-1 h-5 w-5" name="criteria[{{ $key }}]" value="1" type="checkbox" @checked($review['criteria'][$key] ?? false)>
                                <span><strong>{{ $label }}</strong><br>{{ $description }}</span>
                            </label>
                        @endforeach
                    </div>
                    <label class="block space-y-2"><span class="font-semibold">Hallazgos y cambios necesarios</span><span class="block text-sm text-text-secondary">Obligatorio si seleccionás “Requiere cambios”. Indicá qué debe corregirse, sin datos personales.</span><textarea name="findings" maxlength="5000" rows="3" class="w-full rounded border border-border bg-background p-3" placeholder="Describí el cambio concreto que necesita la escena.">{{ $review['findings'] ?? '' }}</textarea></label>
                    <div class="grid gap-3 md:grid-cols-2">
                        <p class="space-y-1"><span class="block font-semibold">Especialidad designada</span><span class="block rounded border border-border bg-background p-3">{{ $specialties[$designation['specialty']] }}</span></p>
                        <label class="space-y-1"><span class="font-semibold">Fecha de revisión</span><input required name="reviewed_on" value="{{ $review['reviewed_on'] ?? now()->toDateString() }}" class="w-full rounded border border-border bg-background p-3" type="date"></label>
                    </div>
                    <fieldset class="space-y-2"><legend class="font-semibold">Estado interno</legend><div class="flex flex-wrap gap-5">
                        <label><input required type="radio" name="status" value="changes_required" @checked(($review['status'] ?? '') === 'changes_required')> Requiere cambios</label>
                        <label><input required type="radio" name="status" value="ready_for_specialist_review" @checked(($review['status'] ?? '') === 'ready_for_specialist_review')> Lista para revisión especializada</label>
                    </div></fieldset>
                    <button class="min-h-11 rounded bg-primary px-5 py-3 font-semibold text-white" type="submit">Guardar revisión interna</button>
                    @if($review)<p class="text-sm">Última actualización: {{ $review['updated_at'] }}</p>@endif
                </form>
            @endforeach
        </div>
        @endif

        <section class="space-y-2 rounded-xl border border-border p-5">
            <h2 class="text-xl font-bold">Límite de este registro</h2>
            <p>No declarar el conjunto “aprobado” hasta resolver hallazgos y obtener dictámenes docente, vial y de accesibilidad por responsables identificados.</p>
        </section>
    </div>
</x-layouts.app>
