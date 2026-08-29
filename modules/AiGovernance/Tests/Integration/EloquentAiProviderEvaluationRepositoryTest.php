<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Enums\AiProviderApprovalStatus;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

uses(RefreshDatabase::class);

function newPersistableAiProviderEvaluation(): AiProviderEvaluation
{
    return AiProviderEvaluation::register(
        id: AiProviderEvaluationId::fromString((string) Str::uuid()),
        providerName: 'OpenAI',
        dataLocation: 'Estados Unidos',
        retentionPolicy: '30 dias',
        securityReviewNotes: 'pendiente',
    );
}

it('guarda y recupera una evaluacion de proveedor por identificador', function (): void {
    $evaluation = newPersistableAiProviderEvaluation();

    app(AiProviderEvaluationRepository::class)->save($evaluation);
    $found = app(AiProviderEvaluationRepository::class)->findById($evaluation->id());

    expect($found)->not->toBeNull()
        ->and($found?->providerName())->toBe('OpenAI')
        ->and($found?->approvalStatus())->toBe(AiProviderApprovalStatus::PendingReview);
});

it('guarda y recupera una evaluacion aprobada con su proxima fecha de revision', function (): void {
    $evaluation = newPersistableAiProviderEvaluation();
    $nextReview = new DateTimeImmutable('2027-08-29T00:00:00+00:00');
    $evaluation->approve(new DateTimeImmutable('2026-08-29T00:00:00+00:00'), $nextReview);

    app(AiProviderEvaluationRepository::class)->save($evaluation);
    $found = app(AiProviderEvaluationRepository::class)->findById($evaluation->id());

    expect($found?->approvalStatus())->toBe(AiProviderApprovalStatus::Approved)
        ->and($found?->nextReviewDueAt())->toEqual($nextReview);
});

it('lista todas las evaluaciones de proveedor registradas', function (): void {
    $repository = app(AiProviderEvaluationRepository::class);
    $repository->save(newPersistableAiProviderEvaluation());
    $repository->save(newPersistableAiProviderEvaluation());

    expect($repository->all())->toHaveCount(2);
});
