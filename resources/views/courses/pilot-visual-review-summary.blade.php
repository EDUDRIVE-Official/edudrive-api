<x-layouts.app title="EDUDRIVE — Cobertura de revisión">
    @php
        $names = ['van' => 'La van que bloquea la vista', 'turn' => 'El carro que gira', 'barrier' => 'Ruta interrumpida', 'descent' => 'Cambió el lugar de descenso'];
        $specialties = ['education' => 'Docencia', 'road_safety' => 'Seguridad vial', 'accessibility' => 'Accesibilidad'];
        $sceneRoutes = ['van' => 'pilot-instruments.van', 'turn' => 'pilot-instruments.visual', 'barrier' => 'pilot-instruments.barrier', 'descent' => 'pilot-instruments.descent'];
    @endphp
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm">Panel interno · Versión {{ $sceneVersion }}</p>
            <h1 class="text-3xl font-bold">Cobertura de revisión especializada</h1>
            <p>Este panel muestra cobertura y cambios pendientes. No concede aprobación pedagógica, vial, técnica ni regulatoria.</p>
            <div class="flex flex-wrap gap-3"><a class="inline-block min-h-11 rounded bg-primary px-4 py-3 font-semibold text-white" href="{{ route('pilot-instruments.visual-readiness') }}">Ver preparación del piloto</a><a class="inline-block min-h-11 rounded border border-border px-4 py-3" href="{{ route('pilot-instruments.visual-review') }}">Registrar mi revisión</a><a class="inline-block min-h-11 rounded border border-border px-4 py-3" href="{{ route('pilot-instruments.visual-reviewers') }}">Gestionar revisores</a><a class="inline-block min-h-11 rounded border border-border px-4 py-3" href="{{ route('pilot-instruments.visual-candidates') }}">Ver candidaturas para lecciones</a><a class="inline-block min-h-11 rounded border border-border px-4 py-3" href="{{ route('pilot-instruments.visual-sequence') }}">Volver al recorrido</a></div>
        </header>

        <div class="grid gap-5 md:grid-cols-2">
            @foreach($coverage as $scene => $state)
                <section class="space-y-3 rounded-xl border border-border bg-surface p-5">
                    <div class="flex items-start justify-between gap-3"><h2 class="text-xl font-bold">{{ $names[$scene] }}</h2><span class="rounded-full px-3 py-1 text-sm {{ $state['coverage_complete'] ? 'bg-green-100 text-green-900' : ($state['has_changes'] ? 'bg-red-100 text-red-900' : 'bg-amber-100 text-amber-900') }}">{{ $state['coverage_complete'] ? 'Cobertura completa' : ($state['has_changes'] ? 'Cambios pendientes' : 'Cobertura incompleta') }}</span></div>
                    <p>Registros recibidos: <strong>{{ $state['reviews'] }}</strong></p>
                    <div><h3 class="font-semibold">Especialidades con revisión lista</h3><ul class="mt-2 flex flex-wrap gap-2">@forelse($state['ready_specialties'] as $specialty)<li class="rounded-full bg-green-100 px-3 py-1 text-green-900">{{ $specialties[$specialty] }}</li>@empty<li>Ninguna todavía</li>@endforelse</ul></div>
                    @if($state['missing_specialties'] !== [])<div><h3 class="font-semibold">Faltan</h3><ul class="list-disc pl-5">@foreach($state['missing_specialties'] as $specialty)<li>{{ $specialties[$specialty] }}</li>@endforeach</ul></div>@endif
                    @if($state['has_changes'])
                        <div class="space-y-3 rounded border border-red-400 bg-red-50 p-3 text-red-950">
                            <p class="font-semibold">Cambios que bloquean la cobertura</p>
                            <ul class="space-y-3">
                                @foreach($state['blockers'] as $blocker)
                                    <li class="rounded bg-white/70 p-3">
                                        <p class="text-sm font-semibold">{{ $specialties[$blocker['specialty']] }} · {{ $blocker['reviewed_on'] }}</p>
                                        <p class="mt-1 whitespace-pre-line">{{ $blocker['finding'] }}</p>
                                    </li>
                                @endforeach
                            </ul>
                            <p>Debe corregirse la escena y actualizarse la revisión antes de completar la cobertura.</p>
                        </div>
                    @endif
                    <a class="inline-flex min-h-11 items-center font-semibold text-primary underline" href="{{ route($sceneRoutes[$scene]) }}">Abrir escena para revisar</a>
                </section>
            @endforeach
        </div>

        <aside class="rounded-lg border border-amber-400 bg-amber-50 p-4 text-amber-950"><strong>Criterio del panel:</strong> una escena solo muestra cobertura completa cuando las tres especialidades registraron “lista para revisión especializada” y no existe ningún registro vigente que requiera cambios. Esto sigue sin equivaler a una aprobación final.</aside>
    </div>
</x-layouts.app>
