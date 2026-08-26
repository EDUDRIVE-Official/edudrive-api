<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\RoadPassportHistoryType;
use Modules\RoadPassport\Domain\Enums\RoadPassportStatus;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\PassportHistoryEntry;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models\RoadPassportHistoryEntryModel;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models\RoadPassportModel;

final readonly class EloquentRoadPassportRepository implements RoadPassportRepository
{
    public function save(RoadPassport $passport): void
    {
        DB::transaction(function () use ($passport): void {
            $model = RoadPassportModel::query()->updateOrCreate(
                ['id' => $passport->id()->value()],
                [
                    'user_id' => $passport->userId(),
                    'status' => $passport->status()->value,
                    'level' => $passport->level(),
                    'issued_at' => $passport->issuedAt(),
                ],
            );

            $model->historyEntries()->delete();

            foreach ($passport->history() as $entry) {
                RoadPassportHistoryEntryModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'road_passport_id' => $model->id,
                    'type' => $entry->type->value,
                    'from_value' => $entry->fromValue,
                    'to_value' => $entry->toValue,
                    'reason' => $entry->reason,
                    'occurred_at' => $entry->occurredAt,
                ]);
            }
        });
    }

    public function findById(RoadPassportId $id): ?RoadPassport
    {
        $model = RoadPassportModel::query()->with('historyEntries')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByUserId(string $userId): ?RoadPassport
    {
        $model = RoadPassportModel::query()->with('historyEntries')->where('user_id', $userId)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    private function toDomain(RoadPassportModel $model): RoadPassport
    {
        /** @var list<RoadPassportHistoryEntryModel> $historyModels */
        $historyModels = array_values($model->historyEntries->all());

        return RoadPassport::restore(
            id: RoadPassportId::fromString((string) $model->getAttribute('id')),
            userId: (string) $model->getAttribute('user_id'),
            status: RoadPassportStatus::from((string) $model->getAttribute('status')),
            level: (int) $model->getAttribute('level'),
            issuedAt: new DateTimeImmutable((string) $model->getAttribute('issued_at')),
            history: array_map(
                static fn (RoadPassportHistoryEntryModel $entry): PassportHistoryEntry => PassportHistoryEntry::restore(
                    RoadPassportHistoryType::from((string) $entry->getAttribute('type')),
                    (string) $entry->getAttribute('from_value'),
                    (string) $entry->getAttribute('to_value'),
                    new DateTimeImmutable((string) $entry->getAttribute('occurred_at')),
                    $entry->getAttribute('reason') === null ? null : (string) $entry->getAttribute('reason'),
                ),
                $historyModels,
            ),
        );
    }
}
