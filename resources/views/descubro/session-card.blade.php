@if (\Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::enabled())
    @php($descubroRun = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::progress(request()))
    @if ($descubroRun['phase'] !== 'home' || $descubroRun['completed_once'])
        @php($practiceSummary = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::summary($descubroRun))
        <section class="no-print ed-placa-descubro" aria-labelledby="descubro-practice-title">
            <p class="inline-flex items-center gap-2 text-base font-bold text-secondary"><x-ui.stage-plate stage="E1" size="sm" />DESCUBRO · Vista de revisión</p>
            <h2 id="descubro-practice-title" class="mt-2 text-3xl">Cruzar con acompañamiento</h2>
            <p class="mt-2 text-lg"><strong>{{ $descubroRun['completed_once'] ? 'Práctica digital completada' : 'Práctica digital en curso' }}</strong> · Habilidad en desarrollo</p>
            @if ($descubroRun['phase'] !== 'result')
                <p class="mt-2 text-lg">{{ $practiceSummary['phase'] }}</p>
            @endif
            @include('descubro.skills')
            <p class="mt-4 text-lg"><strong>Siguiente paso:</strong> {{ $practiceSummary['next'] }}</p>
            <p class="mt-2 text-base text-text-secondary">Demostración en circuito protegido: pendiente de observación.</p>
            <p class="mt-2 text-base text-text-secondary">Avance guardado en tu cuenta. Podés cerrar sesión y retomarlo después. Este registro interno no se incorpora a las evidencias del Pasaporte, no modifica su nivel ni acredita dominio.</p>
            <a class="ed-btn mt-4" href="{{ route('descubro.crossing.show') }}">Volver a DESCUBRO →</a>
        </section>
    @endif
@endif
