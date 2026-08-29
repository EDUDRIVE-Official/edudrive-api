<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Enums\AiSystemStatus;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models\AiSystemModel;

final readonly class EloquentAiSystemRepository implements AiSystemRepository
{
    public function save(AiSystem $system): void
    {
        AiSystemModel::query()->updateOrCreate(
            ['id' => $system->id()->value()],
            [
                'name' => $system->name(),
                'purpose' => $system->purpose(),
                'functional_owner_id' => $system->functionalOwnerId(),
                'technical_owner_id' => $system->technicalOwnerId(),
                'risk_level' => $system->riskLevel()->value,
                'supervision_level' => $system->supervisionLevel()->value,
                'data_categories' => array_map(static fn (AiDataCategory $category): string => $category->value, $system->dataCategories()),
                'status' => $system->status()->value,
                'extraordinary_approval_granted' => $system->extraordinaryApprovalGranted(),
                'extraordinary_approval_at' => $system->extraordinaryApprovalAt(),
                'committee_approved' => $system->committeeApproved(),
                'committee_approved_at' => $system->committeeApprovedAt(),
                'provider_evaluation_id' => $system->providerEvaluationId(),
                'registered_at' => $system->registeredAt(),
            ],
        );
    }

    public function findById(AiSystemId $id): ?AiSystem
    {
        $model = AiSystemModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AiSystem> */
    public function all(): array
    {
        return array_values(
            AiSystemModel::query()
                ->orderBy('registered_at')
                ->get()
                ->map(fn (AiSystemModel $model): AiSystem => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AiSystemModel $model): AiSystem
    {
        /** @var list<string> $categories */
        $categories = $model->getAttribute('data_categories') ?? [];
        $extraordinaryApprovalAt = $model->getAttribute('extraordinary_approval_at');
        $committeeApprovedAt = $model->getAttribute('committee_approved_at');

        return AiSystem::restore(
            id: AiSystemId::fromString((string) $model->getAttribute('id')),
            name: (string) $model->getAttribute('name'),
            purpose: (string) $model->getAttribute('purpose'),
            functionalOwnerId: (string) $model->getAttribute('functional_owner_id'),
            technicalOwnerId: $model->getAttribute('technical_owner_id') === null ? null : (string) $model->getAttribute('technical_owner_id'),
            riskLevel: AiRiskLevel::from((string) $model->getAttribute('risk_level')),
            supervisionLevel: AiSupervisionLevel::from((int) $model->getAttribute('supervision_level')),
            dataCategories: array_map(static fn (string $category): AiDataCategory => AiDataCategory::from($category), $categories),
            status: AiSystemStatus::from((string) $model->getAttribute('status')),
            extraordinaryApprovalGranted: (bool) $model->getAttribute('extraordinary_approval_granted'),
            extraordinaryApprovalAt: $extraordinaryApprovalAt === null ? null : new DateTimeImmutable((string) $extraordinaryApprovalAt),
            committeeApproved: (bool) $model->getAttribute('committee_approved'),
            committeeApprovedAt: $committeeApprovedAt === null ? null : new DateTimeImmutable((string) $committeeApprovedAt),
            providerEvaluationId: $model->getAttribute('provider_evaluation_id') === null ? null : (string) $model->getAttribute('provider_evaluation_id'),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
        );
    }
}
