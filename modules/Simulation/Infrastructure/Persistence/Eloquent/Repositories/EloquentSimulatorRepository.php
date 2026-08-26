<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Enums\SimulatorStatus;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorHistoryEntry;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulatorHistoryEntryModel;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulatorModel;

final readonly class EloquentSimulatorRepository implements SimulatorRepository
{
    public function save(Simulator $simulator): void
    {
        DB::transaction(function () use ($simulator): void {
            $model = SimulatorModel::query()->updateOrCreate(
                ['id' => $simulator->id()->value()],
                [
                    'device_identifier' => $simulator->deviceIdentifier()->value(),
                    'software_version' => $simulator->softwareVersion(),
                    'location' => $simulator->location(),
                    'status' => $simulator->status()->value,
                    'integration_key_hash' => $simulator->integrationKey()->hash(),
                    'registered_at' => $simulator->registeredAt(),
                ],
            );

            $model->historyEntries()->delete();

            foreach ($simulator->history() as $entry) {
                SimulatorHistoryEntryModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'simulator_id' => $model->id,
                    'from_status' => $entry->fromStatus->value,
                    'to_status' => $entry->toStatus->value,
                    'reason' => $entry->reason,
                    'occurred_at' => $entry->occurredAt,
                ]);
            }
        });
    }

    public function findById(SimulatorId $id): ?Simulator
    {
        $model = SimulatorModel::query()->with('historyEntries')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByDeviceIdentifier(DeviceIdentifier $deviceIdentifier): ?Simulator
    {
        $model = SimulatorModel::query()->with('historyEntries')
            ->where('device_identifier', $deviceIdentifier->value())
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?Simulator
    {
        $model = SimulatorModel::query()->with('historyEntries')
            ->where('integration_key_hash', $integrationKeyHash)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Simulator> */
    public function all(): array
    {
        return array_values(
            SimulatorModel::query()->with('historyEntries')
                ->orderBy('registered_at')
                ->get()
                ->map(fn (SimulatorModel $model): Simulator => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(SimulatorModel $model): Simulator
    {
        /** @var list<SimulatorHistoryEntryModel> $historyModels */
        $historyModels = array_values($model->historyEntries->all());

        return Simulator::restore(
            id: SimulatorId::fromString((string) $model->getAttribute('id')),
            deviceIdentifier: DeviceIdentifier::fromString((string) $model->getAttribute('device_identifier')),
            softwareVersion: (string) $model->getAttribute('software_version'),
            location: $model->getAttribute('location') === null ? null : (string) $model->getAttribute('location'),
            status: SimulatorStatus::from((string) $model->getAttribute('status')),
            integrationKey: IntegrationKey::fromHash((string) $model->getAttribute('integration_key_hash')),
            registeredAt: new DateTimeImmutable((string) $model->getAttribute('registered_at')),
            history: array_map(
                static fn (SimulatorHistoryEntryModel $entry): SimulatorHistoryEntry => SimulatorHistoryEntry::restore(
                    SimulatorStatus::from((string) $entry->getAttribute('from_status')),
                    SimulatorStatus::from((string) $entry->getAttribute('to_status')),
                    new DateTimeImmutable((string) $entry->getAttribute('occurred_at')),
                    $entry->getAttribute('reason') === null ? null : (string) $entry->getAttribute('reason'),
                ),
                $historyModels,
            ),
        );
    }
}
