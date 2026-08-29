<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\Enums\AiIncidentSeverity;
use Modules\AiGovernance\Domain\Enums\AiIncidentStatus;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models\AiIncidentModel;

final readonly class EloquentAiIncidentRepository implements AiIncidentRepository
{
    public function save(AiIncident $incident): void
    {
        AiIncidentModel::query()->updateOrCreate(
            ['id' => $incident->id()],
            [
                'ai_system_id' => $incident->aiSystemId(),
                'severity' => $incident->severity()->value,
                'description' => $incident->description(),
                'status' => $incident->status()->value,
                'corrective_actions' => $incident->correctiveActions(),
                'discovered_at' => $incident->discoveredAt(),
                'resolved_at' => $incident->resolvedAt(),
            ],
        );
    }

    public function findById(string $id): ?AiIncident
    {
        $model = AiIncidentModel::query()->where('id', $id)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AiIncident> */
    public function findByAiSystem(AiSystemId $aiSystemId): array
    {
        return array_values(
            AiIncidentModel::query()
                ->where('ai_system_id', $aiSystemId->value())
                ->orderBy('discovered_at', 'desc')
                ->get()
                ->map(fn (AiIncidentModel $model): AiIncident => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AiIncidentModel $model): AiIncident
    {
        $resolvedAt = $model->getAttribute('resolved_at');

        return AiIncident::restore(
            id: (string) $model->getAttribute('id'),
            aiSystemId: (string) $model->getAttribute('ai_system_id'),
            severity: AiIncidentSeverity::from((string) $model->getAttribute('severity')),
            description: (string) $model->getAttribute('description'),
            status: AiIncidentStatus::from((string) $model->getAttribute('status')),
            correctiveActions: $model->getAttribute('corrective_actions') === null ? null : (string) $model->getAttribute('corrective_actions'),
            discoveredAt: new DateTimeImmutable((string) $model->getAttribute('discovered_at')),
            resolvedAt: $resolvedAt === null ? null : new DateTimeImmutable((string) $resolvedAt),
        );
    }
}
