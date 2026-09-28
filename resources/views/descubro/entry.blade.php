@if (\Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::enabled())
    @php
        $descubroProfileRun = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::progress(request());
        $practiceSummary = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::summary($descubroProfileRun);
    @endphp
    <section class="rounded-xl border border-secondary/30 bg-surface p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-secondary">DESCUBRO · 3–6 años · Vista de revisión</p>
        <h2 class="mt-2 font-heading text-xl font-bold">Cruzar con acompañamiento</h2>
        <p class="mt-2 text-sm text-text-secondary">{{ $practiceSummary['phase'] }}</p>
        @include('descubro.skills')
        <p class="mt-4 text-sm"><strong>Siguiente paso:</strong> {{ $practiceSummary['next'] }}</p>
        <a href="{{ route('descubro.crossing.show') }}" class="mt-4 inline-flex min-h-11 items-center rounded-md bg-primary px-5 font-bold text-white">{{ $practiceSummary['action'] }} →</a>
    </section>
@endif
