<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Foundation\Infrastructure\Persistence\DatabaseDate;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionHistoryEntry;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulationSessionHistoryEntryModel;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulationSessionModel;

final readonly class EloquentSimulationSessionRepository implements SimulationSessionRepository
{
    public function save(SimulationSession $session): void
    {
        DB::transaction(function () use ($session): void {
            $model = SimulationSessionModel::query()->updateOrCreate(
                ['id' => $session->id()->value()],
                [
                    'user_id' => $session->userId(),
                    'simulator_id' => $session->simulatorId(),
                    'vehicle_type' => $session->vehicleType(),
                    'scenario' => $session->scenario(),
                    'scheduled_at' => $this->databaseDate($session->scheduledAt()),
                    'planned_duration_minutes' => $session->plannedDurationMinutes(),
                    'status' => $session->status()->value,
                    'started_at' => $this->databaseDate($session->startedAt()),
                    'ended_at' => $this->databaseDate($session->endedAt()),
                ],
            );

            $model->historyEntries()->delete();

            foreach ($session->history() as $entry) {
                SimulationSessionHistoryEntryModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'simulation_session_id' => $model->id,
                    'from_status' => $entry->fromStatus->value,
                    'to_status' => $entry->toStatus->value,
                    'reason' => $entry->reason,
                    'occurred_at' => $this->databaseDate($entry->occurredAt),
                ]);
            }
        });
    }

    public function findById(SimulationSessionId $id): ?SimulationSession
    {
        $model = SimulationSessionModel::query()->with('historyEntries')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<SimulationSession> */
    public function allForUser(string $userId): array
    {
        return array_values(
            SimulationSessionModel::query()->with('historyEntries')
                ->where('user_id', $userId)
                ->orderBy('scheduled_at')
                ->get()
                ->map(fn (SimulationSessionModel $model): SimulationSession => $this->toDomain($model))
                ->all(),
        );
    }

    /** @return list<SimulationSession> */
    public function all(): array
    {
        return array_values(
            SimulationSessionModel::query()->with('historyEntries')
                ->orderBy('scheduled_at')
                ->get()
                ->map(fn (SimulationSessionModel $model): SimulationSession => $this->toDomain($model))
                ->all(),
        );
    }

    private function databaseDate(?DateTimeImmutable $date): ?DateTimeImmutable
    {
        // SQL timestamps omit the offset; use the same zone Eloquent uses when reading.
        return $date?->setTimezone(new DateTimeZone((string) config('app.timezone')));
    }

    private function toDomain(SimulationSessionModel $model): SimulationSession
    {
        /** @var list<SimulationSessionHistoryEntryModel> $historyModels */
        $historyModels = array_values($model->historyEntries->all());

        $startedAt = $model->getAttribute('started_at');
        $endedAt = $model->getAttribute('ended_at');

        return SimulationSession::restore(
            id: SimulationSessionId::fromString((string) $model->getAttribute('id')),
            userId: (string) $model->getAttribute('user_id'),
            simulatorId: (string) $model->getAttribute('simulator_id'),
            vehicleType: (string) $model->getAttribute('vehicle_type'),
            scenario: (string) $model->getAttribute('scenario'),
            scheduledAt: DatabaseDate::restore($model->getAttribute('scheduled_at')),
            plannedDurationMinutes: (int) $model->getAttribute('planned_duration_minutes'),
            status: SimulationSessionStatus::from((string) $model->getAttribute('status')),
            startedAt: $startedAt === null ? null : DatabaseDate::restore($startedAt),
            endedAt: $endedAt === null ? null : DatabaseDate::restore($endedAt),
            history: array_map(
                static fn (SimulationSessionHistoryEntryModel $entry): SimulationSessionHistoryEntry => SimulationSessionHistoryEntry::restore(
                    SimulationSessionStatus::from((string) $entry->getAttribute('from_status')),
                    SimulationSessionStatus::from((string) $entry->getAttribute('to_status')),
                    DatabaseDate::restore($entry->getAttribute('occurred_at')),
                    $entry->getAttribute('reason') === null ? null : (string) $entry->getAttribute('reason'),
                ),
                $historyModels,
            ),
        );
    }
}
