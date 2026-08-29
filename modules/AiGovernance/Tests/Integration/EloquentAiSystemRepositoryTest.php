<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Enums\AiSystemStatus;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

uses(RefreshDatabase::class);

function newPersistableAiSystem(): AiSystem
{
    return AiSystem::register(
        id: AiSystemId::fromString((string) Str::uuid()),
        name: 'Recomendador de aprendizaje',
        purpose: 'Recomendar rutas de aprendizaje personalizadas',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: (string) Str::uuid(),
        riskLevel: AiRiskLevel::Ia2,
        supervisionLevel: AiSupervisionLevel::Recommends,
        dataCategories: [AiDataCategory::Personal, AiDataCategory::Minors],
        registeredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('guarda y recupera un sistema de IA por identificador', function (): void {
    $system = newPersistableAiSystem();

    app(AiSystemRepository::class)->save($system);
    $found = app(AiSystemRepository::class)->findById($system->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($system->id()))->toBeTrue()
        ->and($found?->name())->toBe('Recomendador de aprendizaje')
        ->and($found?->riskLevel())->toBe(AiRiskLevel::Ia2)
        ->and($found?->supervisionLevel())->toBe(AiSupervisionLevel::Recommends)
        ->and($found?->dataCategories())->toBe([AiDataCategory::Personal, AiDataCategory::Minors])
        ->and($found?->status())->toBe(AiSystemStatus::Evaluation);
});

it('guarda y recupera las aprobaciones y su estado', function (): void {
    $system = newPersistableAiSystem();
    $system->grantExtraordinaryApproval(new DateTimeImmutable('2026-08-30T00:00:00+00:00'));
    $system->approveByCommittee(new DateTimeImmutable('2026-08-30T00:00:00+00:00'));
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));

    app(AiSystemRepository::class)->save($system);
    $found = app(AiSystemRepository::class)->findById($system->id());

    expect($found?->extraordinaryApprovalGranted())->toBeTrue()
        ->and($found?->committeeApproved())->toBeTrue()
        ->and($found?->status())->toBe(AiSystemStatus::Pilot);
});

it('lista todos los sistemas de IA registrados', function (): void {
    $repository = app(AiSystemRepository::class);
    $repository->save(newPersistableAiSystem());
    $repository->save(newPersistableAiSystem());

    expect($repository->all())->toHaveCount(2);
});
