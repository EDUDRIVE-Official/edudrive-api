<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Enums\AiModelStatus;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models\AiModelModel;

final readonly class EloquentAiModelRepository implements AiModelRepository
{
    public function save(AiModel $model): void
    {
        AiModelModel::query()->updateOrCreate(
            ['id' => $model->id()->value()],
            [
                'name' => $model->name(),
                'provider' => $model->provider(),
                'version' => $model->version(),
                'owner_id' => $model->ownerId(),
                'use_case' => $model->useCase(),
                'status' => $model->status()->value,
                'known_risks' => $model->knownRisks(),
                'registered_at' => $model->registeredAt(),
            ],
        );
    }

    public function findById(AiModelId $id): ?AiModel
    {
        $model = AiModelModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AiModel> */
    public function all(): array
    {
        return array_values(
            AiModelModel::query()
                ->orderBy('registered_at')
                ->get()
                ->map(fn (AiModelModel $model): AiModel => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AiModelModel $model): AiModel
    {
        return AiModel::restore(
            id: AiModelId::fromString((string) $model->getAttribute('id')),
            name: (string) $model->getAttribute('name'),
            provider: (string) $model->getAttribute('provider'),
            version: (string) $model->getAttribute('version'),
            ownerId: $model->getAttribute('owner_id') === null ? null : (string) $model->getAttribute('owner_id'),
            useCase: $model->getAttribute('use_case') === null ? null : (string) $model->getAttribute('use_case'),
            status: AiModelStatus::from((string) $model->getAttribute('status')),
            knownRisks: $model->getAttribute('known_risks') === null ? null : (string) $model->getAttribute('known_risks'),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
        );
    }
}
