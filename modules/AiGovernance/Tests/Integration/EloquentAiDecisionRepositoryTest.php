<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Enums\AiDecisionReviewStatus;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

uses(RefreshDatabase::class);

function persistedAiSystemIdForDecision(): string
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

it('guarda y recupera una decision de IA por identificador', function (): void {
    $aiSystemId = persistedAiSystemIdForDecision();
    $decision = AiDecision::record(
        id: (string) Str::uuid(),
        aiSystemId: $aiSystemId,
        requestedByUserId: (string) Str::uuid(),
        inputSummary: 'entrada',
        outputSummary: 'salida',
        requiresReview: true,
        confidenceLevel: 0.9,
        tokensInput: 100,
        tokensOutput: 50,
        costAmount: 0.0025,
        latencyMs: 400,
    );

    app(AiDecisionRepository::class)->save($decision);
    $found = app(AiDecisionRepository::class)->findById($decision->id());

    expect($found)->not->toBeNull()
        ->and($found?->aiSystemId())->toBe($aiSystemId)
        ->and($found?->reviewStatus())->toBe(AiDecisionReviewStatus::Pending)
        ->and($found?->confidenceLevel())->toBe(0.9)
        ->and($found?->tokensInput())->toBe(100)
        ->and($found?->costAmount())->toBe(0.0025);
});

it('guarda y recupera una decision revisada', function (): void {
    $decision = AiDecision::record(
        id: (string) Str::uuid(),
        aiSystemId: persistedAiSystemIdForDecision(),
        requestedByUserId: null,
        inputSummary: 'entrada',
        outputSummary: 'salida',
        requiresReview: true,
    );
    $reviewerId = (string) Str::uuid();
    $decision->approve($reviewerId, new DateTimeImmutable('2026-08-30T00:00:00+00:00'));

    app(AiDecisionRepository::class)->save($decision);
    $found = app(AiDecisionRepository::class)->findById($decision->id());

    expect($found?->reviewStatus())->toBe(AiDecisionReviewStatus::Approved)
        ->and($found?->reviewedByUserId())->toBe($reviewerId);
});

it('lista las decisiones de un sistema de IA', function (): void {
    $aiSystemId = AiSystemId::fromString(persistedAiSystemIdForDecision());
    $otherAiSystemId = persistedAiSystemIdForDecision();
    $repository = app(AiDecisionRepository::class);
    $repository->save(AiDecision::record((string) Str::uuid(), $aiSystemId->value(), null, 'a', 'b', false));
    $repository->save(AiDecision::record((string) Str::uuid(), $aiSystemId->value(), null, 'c', 'd', false));
    $repository->save(AiDecision::record((string) Str::uuid(), $otherAiSystemId, null, 'e', 'f', false));

    expect($repository->findByAiSystem($aiSystemId))->toHaveCount(2);
});
