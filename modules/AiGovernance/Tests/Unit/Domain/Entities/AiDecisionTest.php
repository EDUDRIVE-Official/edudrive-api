<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Enums\AiDecisionReviewStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiDecisionReview;

function newAiDecision(bool $requiresReview = false): AiDecision
{
    return AiDecision::record(
        id: (string) Str::uuid(),
        aiSystemId: (string) Str::uuid(),
        requestedByUserId: (string) Str::uuid(),
        inputSummary: 'Estudiante consulta ruta de aprendizaje',
        outputSummary: 'Se recomienda el modulo 3',
        requiresReview: $requiresReview,
        confidenceLevel: 0.87,
        tokensInput: 120,
        tokensOutput: 45,
        costAmount: 0.002,
        latencyMs: 350,
        occurredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se registra sin revision cuando el sistema no la requiere', function (): void {
    $decision = newAiDecision(requiresReview: false);

    expect($decision->reviewStatus())->toBe(AiDecisionReviewStatus::NotRequired)
        ->and($decision->confidenceLevel())->toBe(0.87)
        ->and($decision->tokensInput())->toBe(120)
        ->and($decision->tokensOutput())->toBe(45)
        ->and($decision->costAmount())->toBe(0.002)
        ->and($decision->latencyMs())->toBe(350);
});

it('se registra pendiente de revision cuando el sistema la requiere', function (): void {
    $decision = newAiDecision(requiresReview: true);

    expect($decision->reviewStatus())->toBe(AiDecisionReviewStatus::Pending);
});

it('aprueba una decision pendiente', function (): void {
    $decision = newAiDecision(requiresReview: true);
    $reviewerId = (string) Str::uuid();

    $decision->approve($reviewerId, new DateTimeImmutable('2026-08-30T00:00:00+00:00'));

    expect($decision->reviewStatus())->toBe(AiDecisionReviewStatus::Approved)
        ->and($decision->reviewedByUserId())->toBe($reviewerId)
        ->and($decision->reviewedAt())->not->toBeNull();
});

it('rechaza una decision pendiente', function (): void {
    $decision = newAiDecision(requiresReview: true);
    $reviewerId = (string) Str::uuid();

    $decision->reject($reviewerId, new DateTimeImmutable('2026-08-30T00:00:00+00:00'));

    expect($decision->reviewStatus())->toBe(AiDecisionReviewStatus::Rejected);
});

it('rechaza aprobar o rechazar una decision que no esta pendiente', function (): void {
    $notRequired = newAiDecision(requiresReview: false);
    expect(fn () => $notRequired->approve((string) Str::uuid(), new DateTimeImmutable('now')))
        ->toThrow(InvalidAiDecisionReview::class);

    $alreadyApproved = newAiDecision(requiresReview: true);
    $alreadyApproved->approve((string) Str::uuid(), new DateTimeImmutable('now'));
    expect(fn () => $alreadyApproved->reject((string) Str::uuid(), new DateTimeImmutable('now')))
        ->toThrow(InvalidAiDecisionReview::class);
});

it('restaura la entidad completa desde persistencia', function (): void {
    $id = (string) Str::uuid();
    $aiSystemId = (string) Str::uuid();
    $occurredAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $reviewedAt = new DateTimeImmutable('2026-08-30T00:00:00+00:00');

    $decision = AiDecision::restore(
        id: $id,
        aiSystemId: $aiSystemId,
        requestedByUserId: 'user-1',
        inputSummary: 'entrada',
        outputSummary: 'salida',
        confidenceLevel: 0.5,
        tokensInput: 10,
        tokensOutput: 20,
        costAmount: 0.01,
        latencyMs: 100,
        reviewStatus: AiDecisionReviewStatus::Approved,
        reviewedByUserId: 'reviewer-1',
        reviewedAt: $reviewedAt,
        occurredAt: $occurredAt,
    );

    expect($decision->id())->toBe($id)
        ->and($decision->aiSystemId())->toBe($aiSystemId)
        ->and($decision->reviewStatus())->toBe(AiDecisionReviewStatus::Approved)
        ->and($decision->reviewedByUserId())->toBe('reviewer-1')
        ->and($decision->reviewedAt())->toBe($reviewedAt)
        ->and($decision->occurredAt())->toBe($occurredAt);
});
