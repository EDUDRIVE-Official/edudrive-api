<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Simulation\Domain\Entities\TelemetrySample;
use Modules\Simulation\Domain\Repositories\TelemetrySampleRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\TelemetrySampleModel;

final readonly class EloquentTelemetrySampleRepository implements TelemetrySampleRepository
{
    /** @param list<TelemetrySample> $samples */
    public function saveBatch(array $samples): int
    {
        if ($samples === []) {
            return 0;
        }

        return TelemetrySampleModel::query()->insertOrIgnore(array_map(
            static fn (TelemetrySample $sample): array => [
                'id' => $sample->id(),
                'simulation_session_id' => $sample->sessionId(),
                'speed_kph' => $sample->speedKph(),
                'braking_percentage' => $sample->brakingPercentage(),
                'acceleration_mps2' => $sample->accelerationMps2(),
                'steering_angle_degrees' => $sample->steeringAngleDegrees(),
                'recorded_at' => $sample->recordedAt(),
                'created_at' => new DateTimeImmutable('now'),
                'updated_at' => new DateTimeImmutable('now'),
            ],
            $samples,
        ));
    }

    /** @return list<TelemetrySample> */
    public function allForSession(string $sessionId): array
    {
        return array_values(
            TelemetrySampleModel::query()
                ->where('simulation_session_id', $sessionId)
                ->orderBy('recorded_at')
                ->get()
                ->map(static fn (TelemetrySampleModel $model): TelemetrySample => TelemetrySample::record(
                    id: (string) $model->getAttribute('id'),
                    sessionId: (string) $model->getAttribute('simulation_session_id'),
                    speedKph: (float) $model->getAttribute('speed_kph'),
                    brakingPercentage: (float) $model->getAttribute('braking_percentage'),
                    accelerationMps2: (float) $model->getAttribute('acceleration_mps2'),
                    steeringAngleDegrees: (float) $model->getAttribute('steering_angle_degrees'),
                    recordedAt: new DateTimeImmutable((string) $model->getAttribute('recorded_at')),
                ))
                ->all(),
        );
    }
}
