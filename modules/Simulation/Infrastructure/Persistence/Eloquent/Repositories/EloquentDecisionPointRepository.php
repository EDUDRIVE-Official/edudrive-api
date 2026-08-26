<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\DecisionPointModel;

final readonly class EloquentDecisionPointRepository implements DecisionPointRepository
{
    /** @param list<DecisionPoint> $points */
    public function saveBatch(array $points): int
    {
        if ($points === []) {
            return 0;
        }

        return DecisionPointModel::query()->insertOrIgnore(array_map(
            static fn (DecisionPoint $point): array => [
                'id' => $point->id(),
                'simulation_session_id' => $point->sessionId(),
                'road_context' => $point->roadContext(),
                'risk_level' => $point->riskLevel()->value,
                'driver_reaction' => $point->driverReaction()->value,
                'occurred_at' => $point->occurredAt(),
                'created_at' => new DateTimeImmutable('now'),
                'updated_at' => new DateTimeImmutable('now'),
            ],
            $points,
        ));
    }

    /** @return list<DecisionPoint> */
    public function allForSession(string $sessionId): array
    {
        return array_values(
            DecisionPointModel::query()
                ->where('simulation_session_id', $sessionId)
                ->orderBy('occurred_at')
                ->get()
                ->map(static fn (DecisionPointModel $model): DecisionPoint => DecisionPoint::record(
                    id: (string) $model->getAttribute('id'),
                    sessionId: (string) $model->getAttribute('simulation_session_id'),
                    roadContext: (string) $model->getAttribute('road_context'),
                    riskLevel: DecisionRiskLevel::from((string) $model->getAttribute('risk_level')),
                    driverReaction: DriverReactionType::from((string) $model->getAttribute('driver_reaction')),
                    occurredAt: new DateTimeImmutable((string) $model->getAttribute('occurred_at')),
                ))
                ->all(),
        );
    }
}
