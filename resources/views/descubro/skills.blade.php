<div class="ds-summary mt-4">
    <h3 class="mb-3 text-2xl">Mis habilidades en esta práctica</h3>
    <ul class="m-0 grid list-none gap-2 p-0">
        @foreach ($practiceSummary['skills'] as $skill)
            <li class="ed-habilidad" data-competency="{{ $skill['code'] }}">
                <span class="min-w-[150px] flex-1 leading-6">{{ $skill['title'] }}</span>
                <span class="ed-estado ed-estado--{{ $skill['status'] }}"><span aria-hidden="true">{{ $skill['status'] === 'practiced' ? '✓' : ($skill['status'] === 'in_progress' ? '◐' : '○') }}</span>{{ $skill['label'] }}</span>
            </li>
        @endforeach
    </ul>
    @if ($practiceSummary['repeating'])
        <p class="mt-3 text-base">Estás practicando de nuevo. Este resumen conserva la vuelta que ya completaste.</p>
    @endif
    <p class="ed-aviso mt-3 text-base text-text"><x-ui.icon name="info" />Practicado en pantalla no significa dominio. La observación con una persona adulta en un circuito protegido sigue pendiente.</p>
</div>
