<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\Enums\AiIncidentSeverity;
use Modules\AiGovernance\Domain\Enums\AiIncidentStatus;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

uses(RefreshDatabase::class);

function persistedAiSystemIdForIncident(): string
{
    $system = AiSystem::register(
        id: AiSystemId::fromString((string) Str::uuid()),
        name: 'Sistema de prueba',
        purpose: 'Prueba',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: null,
        riskLevel: AiRiskLevel::Ia1,
        supervisionLevel: AiSupervisionLevel::Recommends,
        dataCategories: [],
    );
    app(AiSystemRepository::class)->save($system);

    return $system->id()->value();
}

it('guarda y recupera un incidente de IA por identificador', function (): void {
    $incident = AiIncident::report(
        id: (string) Str::uuid(),
        aiSystemId: persistedAiSystemIdForIncident(),
        severity: AiIncidentSeverity::High,
        description: 'Respuesta con informacion sensible expuesta',
        discoveredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );

    app(AiIncidentRepository::class)->save($incident);
    $found = app(AiIncidentRepository::class)->findById($incident->id());

    expect($found)->not->toBeNull()
        ->and($found?->severity())->toBe(AiIncidentSeverity::High)
        ->and($found?->status())->toBe(AiIncidentStatus::Open);
});

it('guarda y recupera un incidente resuelto', function (): void {
    $incident = AiIncident::report((string) Str::uuid(), persistedAiSystemIdForIncident(), AiIncidentSeverity::Medium, 'descripcion');
    $incident->resolve('acciones correctivas', new DateTimeImmutable('2026-08-30T00:00:00+00:00'));

    app(AiIncidentRepository::class)->save($incident);
    $found = app(AiIncidentRepository::class)->findById($incident->id());

    expect($found?->status())->toBe(AiIncidentStatus::Resolved)
        ->and($found?->correctiveActions())->toBe('acciones correctivas')
        ->and($found?->resolvedAt())->not->toBeNull();
});

it('lista los incidentes de un sistema de IA', function (): void {
    $aiSystemId = AiSystemId::fromString(persistedAiSystemIdForIncident());
    $otherAiSystemId = persistedAiSystemIdForIncident();
    $repository = app(AiIncidentRepository::class);
    $repository->save(AiIncident::report((string) Str::uuid(), $aiSystemId->value(), AiIncidentSeverity::Low, 'a'));
    $repository->save(AiIncident::report((string) Str::uuid(), $aiSystemId->value(), AiIncidentSeverity::Low, 'b'));
    $repository->save(AiIncident::report((string) Str::uuid(), $otherAiSystemId, AiIncidentSeverity::Low, 'c'));

    expect($repository->findByAiSystem($aiSystemId))->toHaveCount(2);
});
