<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Enums\AiProviderApprovalStatus;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models\AiProviderEvaluationModel;

final readonly class EloquentAiProviderEvaluationRepository implements AiProviderEvaluationRepository
{
    public function save(AiProviderEvaluation $evaluation): void
    {
        AiProviderEvaluationModel::query()->updateOrCreate(
            ['id' => $evaluation->id()->value()],
            [
                'provider_name' => $evaluation->providerName(),
                'data_location' => $evaluation->dataLocation(),
                'retention_policy' => $evaluation->retentionPolicy(),
                'security_review_notes' => $evaluation->securityReviewNotes(),
                'approval_status' => $evaluation->approvalStatus()->value,
                'reviewed_at' => $evaluation->reviewedAt(),
                'next_review_due_at' => $evaluation->nextReviewDueAt(),
            ],
        );
    }

    public function findById(AiProviderEvaluationId $id): ?AiProviderEvaluation
    {
        $model = AiProviderEvaluationModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AiProviderEvaluation> */
    public function all(): array
    {
        return array_values(
            AiProviderEvaluationModel::query()
                ->orderBy('created_at')
                ->get()
                ->map(fn (AiProviderEvaluationModel $model): AiProviderEvaluation => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AiProviderEvaluationModel $model): AiProviderEvaluation
    {
        $reviewedAt = $model->getAttribute('reviewed_at');
        $nextReviewDueAt = $model->getAttribute('next_review_due_at');

        return AiProviderEvaluation::restore(
            id: AiProviderEvaluationId::fromString((string) $model->getAttribute('id')),
            providerName: (string) $model->getAttribute('provider_name'),
            dataLocation: (string) $model->getAttribute('data_location'),
            retentionPolicy: (string) $model->getAttribute('retention_policy'),
            securityReviewNotes: $model->getAttribute('security_review_notes') === null ? null : (string) $model->getAttribute('security_review_notes'),
            approvalStatus: AiProviderApprovalStatus::from((string) $model->getAttribute('approval_status')),
            reviewedAt: $reviewedAt === null ? null : new DateTimeImmutable((string) $reviewedAt),
            nextReviewDueAt: $nextReviewDueAt === null ? null : new DateTimeImmutable((string) $nextReviewDueAt),
        );
    }
}
