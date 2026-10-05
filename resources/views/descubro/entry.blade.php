@if (\Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::enabled())
    @php
        $descubroProfileRun = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::progress(request());
        $practiceSummary = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::summary($descubroProfileRun);
    @endphp
    <section class="ed-placa-descubro">
        <p class="inline-flex items-center gap-2 text-base font-bold text-secondary"><x-ui.stage-plate stage="E1" size="sm" />DESCUBRO · 3–6 años · Vista de revisión</p>
        <h2 class="mt-2 text-3xl">Cruzar con acompañamiento</h2>
        <p class="mt-2 text-lg text-text-secondary">{{ $practiceSummary['phase'] }}</p>
        @include('descubro.skills')
        <p class="mt-4 text-lg"><strong>Siguiente paso:</strong> {{ $practiceSummary['next'] }}</p>
        <a href="{{ route('descubro.crossing.show') }}" class="ed-btn mt-4">{{ $practiceSummary['action'] }} →</a>
    </section>
@endif
