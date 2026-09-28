<div class="ds-summary">
    <style>
        .ds-summary{margin-top:18px;color:inherit}.ds-heading{font-size:17px;font-weight:700;margin:0 0 12px}.ds-skills{list-style:none;margin:0;padding:0;display:grid;gap:10px}.ds-skill{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px solid #80978766;border-radius:12px;padding:13px 14px}.ds-name{flex:1;min-width:150px;line-height:1.5;font-size:15px}.ds-status{display:inline-flex;gap:7px;align-items:center;border-radius:20px;padding:5px 10px;font-size:12px;font-weight:650;line-height:1.4;color:#244637;background:#e7eddf}.ds-status-pending{color:#53544d;background:#efeee5}.ds-status-in_progress{color:#695020;background:#fff0cf}.ds-note{font-size:13px;line-height:1.6;margin-top:12px}
    </style>
    <h3 class="ds-heading">Mis habilidades en esta práctica</h3>
    <ul class="ds-skills">
        @foreach ($practiceSummary['skills'] as $skill)
            <li class="ds-skill" data-competency="{{ $skill['code'] }}">
                <span class="ds-name">{{ $skill['title'] }}</span>
                <span class="ds-status ds-status-{{ $skill['status'] }}"><span aria-hidden="true">{{ $skill['status'] === 'practiced' ? '✓' : ($skill['status'] === 'in_progress' ? '◐' : '○') }}</span>{{ $skill['label'] }}</span>
            </li>
        @endforeach
    </ul>
    @if ($practiceSummary['repeating'])
        <p class="ds-note">Estás practicando de nuevo. Este resumen conserva la vuelta que ya completaste.</p>
    @endif
    <p class="ds-note">Practicado en pantalla no significa dominio. La observación con una persona adulta en un circuito protegido sigue pendiente.</p>
</div>
