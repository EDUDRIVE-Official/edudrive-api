@if (\Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::enabled())
    @php($descubroRun = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::progress(request()))
    @if ($descubroRun['phase'] !== 'home' || $descubroRun['completed_once'])
        @php($practiceSummary = \Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController::summary($descubroRun))
        <section class="no-print rounded-xl border border-border bg-surface p-5" aria-labelledby="descubro-practice-title">
            <p class="text-xs font-bold uppercase tracking-wide text-secondary">DESCUBRO · Vista de revisión</p>
            <h2 id="descubro-practice-title" class="mt-2 font-heading text-xl font-bold">Cruzar con acompañamiento</h2>
            <p class="mt-2 text-sm"><strong>{{ $descubroRun['completed_once'] ? 'Práctica digital completada' : 'Práctica digital en curso' }}</strong> · Habilidad en desarrollo</p>
            @if ($descubroRun['phase'] !== 'result')
                <p class="mt-2 text-sm">{{ $practiceSummary['phase'] }}</p>
            @endif
            @include('descubro.skills')
            <p class="mt-4 text-sm"><strong>Siguiente paso:</strong> {{ $practiceSummary['next'] }}</p>
            <p class="mt-2 text-sm text-text-secondary">Demostración en circuito protegido: pendiente de observación.</p>
            <p class="mt-2 text-sm text-text-secondary">Avance guardado en tu cuenta. Podés cerrar sesión y retomarlo después. Este registro interno no se incorpora a las evidencias del Pasaporte, no modifica su nivel ni acredita dominio.</p>
            <a class="mt-4 inline-flex min-h-11 items-center rounded-md bg-primary px-4 font-bold text-white" href="{{ route('descubro.crossing.show') }}">Volver a DESCUBRO →</a>
        </section>
    @endif
@endif
