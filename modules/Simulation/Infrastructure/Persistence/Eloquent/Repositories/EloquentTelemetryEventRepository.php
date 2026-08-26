<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\TelemetryEventModel;

final readonly class EloquentTelemetryEventRepository implements TelemetryEventRepository
{
    /** @param list<TelemetryEvent> $events */
    public function saveBatch(array $events): int
    {
        if ($events === []) {
            return 0;
        }

        return TelemetryEventModel::query()->insertOrIgnore(array_map(
            static fn (TelemetryEvent $event): array => [
                'id' => $event->id(),
                'simulation_session_id' => $event->sessionId(),
                'type' => $event->type()->value,
                'details' => $event->details(),
                'occurred_at' => $event->occurredAt(),
                'created_at' => new DateTimeImmutable('now'),
                'updated_at' => new DateTimeImmutable('now'),
            ],
            $events,
        ));
    }

    /** @return list<TelemetryEvent> */
    public function allForSession(string $sessionId): array
    {
        return array_values(
            TelemetryEventModel::query()
                ->where('simulation_session_id', $sessionId)
                ->orderBy('occurred_at')
                ->get()
                ->map(static fn (TelemetryEventModel $model): TelemetryEvent => TelemetryEvent::record(
                    id: (string) $model->getAttribute('id'),
                    sessionId: (string) $model->getAttribute('simulation_session_id'),
                    type: TelemetryEventType::from((string) $model->getAttribute('type')),
                    details: $model->getAttribute('details') === null ? null : (string) $model->getAttribute('details'),
                    occurredAt: new DateTimeImmutable((string) $model->getAttribute('occurred_at')),
                ))
                ->all(),
        );
    }
}
