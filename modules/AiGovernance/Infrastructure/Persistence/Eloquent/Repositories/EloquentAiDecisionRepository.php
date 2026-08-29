<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Enums\AiDecisionReviewStatus;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models\AiDecisionModel;

final readonly class EloquentAiDecisionRepository implements AiDecisionRepository
{
    public function save(AiDecision $decision): void
    {
        AiDecisionModel::query()->updateOrCreate(
            ['id' => $decision->id()],
            [
                'ai_system_id' => $decision->aiSystemId(),
                'requested_by_user_id' => $decision->requestedByUserId(),
                'input_summary' => $decision->inputSummary(),
                'output_summary' => $decision->outputSummary(),
                'confidence_level' => $decision->confidenceLevel(),
                'tokens_input' => $decision->tokensInput(),
                'tokens_output' => $decision->tokensOutput(),
                'cost_amount' => $decision->costAmount(),
                'latency_ms' => $decision->latencyMs(),
                'review_status' => $decision->reviewStatus()->value,
                'reviewed_by_user_id' => $decision->reviewedByUserId(),
                'reviewed_at' => $decision->reviewedAt(),
                'occurred_at' => $decision->occurredAt(),
            ],
        );
    }

    public function findById(string $id): ?AiDecision
    {
        $model = AiDecisionModel::query()->where('id', $id)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AiDecision> */
    public function findByAiSystem(AiSystemId $aiSystemId): array
    {
        return array_values(
            AiDecisionModel::query()
                ->where('ai_system_id', $aiSystemId->value())
                ->orderBy('occurred_at', 'desc')
                ->get()
                ->map(fn (AiDecisionModel $model): AiDecision => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AiDecisionModel $model): AiDecision
    {
        $confidenceLevel = $model->getAttribute('confidence_level');
        $tokensInput = $model->getAttribute('tokens_input');
        $tokensOutput = $model->getAttribute('tokens_output');
        $costAmount = $model->getAttribute('cost_amount');
        $latencyMs = $model->getAttribute('latency_ms');
        $reviewedByUserId = $model->getAttribute('reviewed_by_user_id');
        $reviewedAt = $model->getAttribute('reviewed_at');

        return AiDecision::restore(
            id: (string) $model->getAttribute('id'),
            aiSystemId: (string) $model->getAttribute('ai_system_id'),
            requestedByUserId: $model->getAttribute('requested_by_user_id') === null ? null : (string) $model->getAttribute('requested_by_user_id'),
            inputSummary: (string) $model->getAttribute('input_summary'),
            outputSummary: (string) $model->getAttribute('output_summary'),
            confidenceLevel: $confidenceLevel === null ? null : (float) $confidenceLevel,
            tokensInput: $tokensInput === null ? null : (int) $tokensInput,
            tokensOutput: $tokensOutput === null ? null : (int) $tokensOutput,
            costAmount: $costAmount === null ? null : (float) $costAmount,
            latencyMs: $latencyMs === null ? null : (int) $latencyMs,
            reviewStatus: AiDecisionReviewStatus::from((string) $model->getAttribute('review_status')),
            reviewedByUserId: $reviewedByUserId === null ? null : (string) $reviewedByUserId,
            reviewedAt: $reviewedAt === null ? null : new DateTimeImmutable((string) $reviewedAt),
            occurredAt: new DateTimeImmutable((string) $model->getAttribute('occurred_at')),
        );
    }
}
