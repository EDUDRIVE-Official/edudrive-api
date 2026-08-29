<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\Enums\AiIncidentSeverity;
use Modules\AiGovernance\Domain\Enums\AiIncidentStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiIncidentTransition;

function newAiIncident(): AiIncident
{
    return AiIncident::report(
        id: (string) Str::uuid(),
        aiSystemId: (string) Str::uuid(),
        severity: AiIncidentSeverity::High,
        description: 'Respuesta con informacion sensible expuesta',
        discoveredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se reporta en estado abierto', function (): void {
    $incident = newAiIncident();

    expect($incident->status())->toBe(AiIncidentStatus::Open)
        ->and($incident->resolvedAt())->toBeNull();
});

it('pasa a investigacion y luego se resuelve', function (): void {
    $incident = newAiIncident();

    $incident->startInvestigation();
    expect($incident->status())->toBe(AiIncidentStatus::Investigating);

    $incident->resolve('Se ajusto el filtro de salida', new DateTimeImmutable('2026-08-30T00:00:00+00:00'));
    expect($incident->status())->toBe(AiIncidentStatus::Resolved)
        ->and($incident->correctiveActions())->toBe('Se ajusto el filtro de salida')
        ->and($incident->resolvedAt())->not->toBeNull();
});

it('permite resolver directamente desde abierto', function (): void {
    $incident = newAiIncident();

    $incident->resolve('Corregido de inmediato', new DateTimeImmutable('now'));

    expect($incident->status())->toBe(AiIncidentStatus::Resolved);
});

it('rechaza iniciar investigacion de un incidente que no esta abierto', function (): void {
    $incident = newAiIncident();
    $incident->startInvestigation();

    expect(fn () => $incident->startInvestigation())->toThrow(InvalidAiIncidentTransition::class);
});

it('rechaza resolver un incidente ya resuelto', function (): void {
    $incident = newAiIncident();
    $incident->resolve('accion', new DateTimeImmutable('now'));

    expect(fn () => $incident->resolve('otra accion', new DateTimeImmutable('now')))
        ->toThrow(InvalidAiIncidentTransition::class);
});

it('restaura la entidad completa desde persistencia', function (): void {
    $id = (string) Str::uuid();
    $aiSystemId = (string) Str::uuid();
    $discoveredAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $resolvedAt = new DateTimeImmutable('2026-08-30T00:00:00+00:00');

    $incident = AiIncident::restore(
        id: $id,
        aiSystemId: $aiSystemId,
        severity: AiIncidentSeverity::Critical,
        description: 'descripcion',
        status: AiIncidentStatus::Resolved,
        correctiveActions: 'acciones',
        discoveredAt: $discoveredAt,
        resolvedAt: $resolvedAt,
    );

    expect($incident->id())->toBe($id)
        ->and($incident->aiSystemId())->toBe($aiSystemId)
        ->and($incident->severity())->toBe(AiIncidentSeverity::Critical)
        ->and($incident->status())->toBe(AiIncidentStatus::Resolved)
        ->and($incident->correctiveActions())->toBe('acciones')
        ->and($incident->discoveredAt())->toBe($discoveredAt)
        ->and($incident->resolvedAt())->toBe($resolvedAt);
});
