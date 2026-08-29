<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Enums\AiProviderApprovalStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiProviderEvaluationTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

function newAiProviderEvaluation(): AiProviderEvaluation
{
    return AiProviderEvaluation::register(
        id: AiProviderEvaluationId::fromString((string) Str::uuid()),
        providerName: 'OpenAI',
        dataLocation: 'Estados Unidos',
        retentionPolicy: '30 dias, sin entrenamiento con datos institucionales',
        securityReviewNotes: 'Pendiente de revision legal',
    );
}

it('se registra en estado pendiente de revision', function (): void {
    $evaluation = newAiProviderEvaluation();

    expect($evaluation->approvalStatus())->toBe(AiProviderApprovalStatus::PendingReview)
        ->and($evaluation->reviewedAt())->toBeNull();
});

it('aprueba una evaluacion con su proxima fecha de revision', function (): void {
    $evaluation = newAiProviderEvaluation();
    $nextReview = new DateTimeImmutable('2027-08-29T00:00:00+00:00');

    $evaluation->approve(new DateTimeImmutable('2026-08-29T00:00:00+00:00'), $nextReview);

    expect($evaluation->approvalStatus())->toBe(AiProviderApprovalStatus::Approved)
        ->and($evaluation->nextReviewDueAt())->toBe($nextReview);
});

it('rechaza una evaluacion', function (): void {
    $evaluation = newAiProviderEvaluation();

    $evaluation->reject(new DateTimeImmutable('now'));

    expect($evaluation->approvalStatus())->toBe(AiProviderApprovalStatus::Rejected);
});

it('marca una evaluacion como que requiere reevaluacion', function (): void {
    $evaluation = newAiProviderEvaluation();
    $evaluation->approve(new DateTimeImmutable('now'), null);

    $evaluation->requireReevaluation(new DateTimeImmutable('now'));

    expect($evaluation->approvalStatus())->toBe(AiProviderApprovalStatus::RequiresReevaluation);
});

it('rechaza aprobar dos veces la misma evaluacion', function (): void {
    $evaluation = newAiProviderEvaluation();
    $evaluation->approve(new DateTimeImmutable('now'), null);

    expect(fn () => $evaluation->approve(new DateTimeImmutable('now'), null))
        ->toThrow(InvalidAiProviderEvaluationTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = AiProviderEvaluationId::fromString((string) Str::uuid());
    $reviewedAt = new DateTimeImmutable('2026-08-29T00:00:00+00:00');
    $nextReviewDueAt = new DateTimeImmutable('2027-08-29T00:00:00+00:00');

    $evaluation = AiProviderEvaluation::restore(
        id: $id,
        providerName: 'Anthropic',
        dataLocation: 'Estados Unidos',
        retentionPolicy: 'sin retencion',
        securityReviewNotes: null,
        approvalStatus: AiProviderApprovalStatus::Approved,
        reviewedAt: $reviewedAt,
        nextReviewDueAt: $nextReviewDueAt,
    );

    expect($evaluation->id()->equals($id))->toBeTrue()
        ->and($evaluation->providerName())->toBe('Anthropic')
        ->and($evaluation->approvalStatus())->toBe(AiProviderApprovalStatus::Approved)
        ->and($evaluation->reviewedAt())->toBe($reviewedAt)
        ->and($evaluation->nextReviewDueAt())->toBe($nextReviewDueAt);
});
