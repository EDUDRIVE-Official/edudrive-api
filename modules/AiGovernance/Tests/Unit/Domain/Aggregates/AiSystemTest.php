<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Enums\AiSystemStatus;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresCommitteeApproval;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresExtraordinaryApproval;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresHumanSupervisionForMinors;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiSystemTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

/** @param list<AiDataCategory> $dataCategories */
function newAiSystem(
    AiRiskLevel $riskLevel = AiRiskLevel::Ia1,
    AiSupervisionLevel $supervisionLevel = AiSupervisionLevel::Recommends,
    array $dataCategories = [],
): AiSystem {
    return AiSystem::register(
        id: AiSystemId::fromString((string) Str::uuid()),
        name: 'Recomendador de aprendizaje',
        purpose: 'Recomendar rutas de aprendizaje personalizadas',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: (string) Str::uuid(),
        riskLevel: $riskLevel,
        supervisionLevel: $supervisionLevel,
        dataCategories: $dataCategories,
        registeredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se registra en estado evaluacion', function (): void {
    $system = newAiSystem();

    expect($system->status())->toBe(AiSystemStatus::Evaluation)
        ->and($system->isUsable())->toBeFalse()
        ->and($system->extraordinaryApprovalGranted())->toBeFalse()
        ->and($system->committeeApproved())->toBeFalse();
});

it('avanza de evaluacion a piloto y a produccion para un riesgo bajo', function (): void {
    $system = newAiSystem(riskLevel: AiRiskLevel::Ia1);

    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));
    expect($system->status())->toBe(AiSystemStatus::Pilot)
        ->and($system->isUsable())->toBeTrue();

    $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now'));
    expect($system->status())->toBe(AiSystemStatus::Production)
        ->and($system->isUsable())->toBeTrue();
});

it('rechaza transiciones invalidas', function (): void {
    $system = newAiSystem();

    expect(fn () => $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now')))
        ->toThrow(InvalidAiSystemTransition::class);
});

it('permite retirar el sistema desde cualquier estado no terminal', function (): void {
    $evaluating = newAiSystem();
    $evaluating->promoteTo(AiSystemStatus::Retired, new DateTimeImmutable('now'));
    expect($evaluating->status())->toBe(AiSystemStatus::Retired);

    expect(fn () => $evaluating->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now')))
        ->toThrow(InvalidAiSystemTransition::class);
});

it('rechaza pasar a produccion un sistema IA-4 sin aprobacion extraordinaria', function (): void {
    $system = newAiSystem(riskLevel: AiRiskLevel::Ia4);
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));

    expect(fn () => $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now')))
        ->toThrow(AiSystemRequiresExtraordinaryApproval::class);

    $system->grantExtraordinaryApproval(new DateTimeImmutable('now'));

    expect(fn () => $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now')))
        ->toThrow(AiSystemRequiresCommitteeApproval::class);

    $system->approveByCommittee(new DateTimeImmutable('now'));
    $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now'));

    expect($system->status())->toBe(AiSystemStatus::Production);
});

it('rechaza pasar a produccion un sistema IA-3 sin aprobacion del comite', function (): void {
    $system = newAiSystem(riskLevel: AiRiskLevel::Ia3);
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));

    expect(fn () => $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now')))
        ->toThrow(AiSystemRequiresCommitteeApproval::class);

    $system->approveByCommittee(new DateTimeImmutable('now'));
    $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now'));

    expect($system->status())->toBe(AiSystemStatus::Production);
});

it('rechaza pasar a produccion un sistema que procesa datos de menores sin supervision suficiente', function (): void {
    $system = newAiSystem(
        riskLevel: AiRiskLevel::Ia2,
        supervisionLevel: AiSupervisionLevel::Recommends,
        dataCategories: [AiDataCategory::Minors],
    );
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));

    expect(fn () => $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now')))
        ->toThrow(AiSystemRequiresHumanSupervisionForMinors::class);
});

it('permite produccion con datos de menores cuando la supervision es propone o superior', function (): void {
    $system = newAiSystem(
        riskLevel: AiRiskLevel::Ia2,
        supervisionLevel: AiSupervisionLevel::Proposes,
        dataCategories: [AiDataCategory::Minors],
    );
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));
    $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now'));

    expect($system->status())->toBe(AiSystemStatus::Production);
});

it('suspende y reactiva un sistema en produccion', function (): void {
    $system = newAiSystem();
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));
    $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now'));

    $system->promoteTo(AiSystemStatus::Suspended, new DateTimeImmutable('now'));
    expect($system->status())->toBe(AiSystemStatus::Suspended)
        ->and($system->isUsable())->toBeFalse();

    $system->promoteTo(AiSystemStatus::Production, new DateTimeImmutable('now'));
    expect($system->status())->toBe(AiSystemStatus::Production);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = AiSystemId::fromString((string) Str::uuid());
    $registeredAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $approvalAt = new DateTimeImmutable('2026-08-30T00:00:00+00:00');

    $system = AiSystem::restore(
        id: $id,
        name: 'Instructor virtual',
        purpose: 'Analizar sesiones practicas',
        functionalOwnerId: 'owner-1',
        technicalOwnerId: 'tech-1',
        riskLevel: AiRiskLevel::Ia3,
        supervisionLevel: AiSupervisionLevel::Proposes,
        dataCategories: [AiDataCategory::Personal, AiDataCategory::Minors],
        status: AiSystemStatus::Production,
        extraordinaryApprovalGranted: false,
        extraordinaryApprovalAt: null,
        committeeApproved: true,
        committeeApprovedAt: $approvalAt,
        providerEvaluationId: 'provider-1',
        registeredAt: $registeredAt,
    );

    expect($system->id()->equals($id))->toBeTrue()
        ->and($system->name())->toBe('Instructor virtual')
        ->and($system->riskLevel())->toBe(AiRiskLevel::Ia3)
        ->and($system->supervisionLevel())->toBe(AiSupervisionLevel::Proposes)
        ->and($system->dataCategories())->toBe([AiDataCategory::Personal, AiDataCategory::Minors])
        ->and($system->status())->toBe(AiSystemStatus::Production)
        ->and($system->committeeApproved())->toBeTrue()
        ->and($system->committeeApprovedAt())->toBe($approvalAt)
        ->and($system->providerEvaluationId())->toBe('provider-1')
        ->and($system->registeredAt())->toBe($registeredAt);
});
