<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Enums\RoadPassportHistoryType;
use Modules\RoadPassport\Domain\Enums\RoadPassportStatus;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;
use Modules\RoadPassport\Domain\ValueObjects\PassportHistoryEntry;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models\RoadPassportEvidenceModel;
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

            $model->evidenceEntries()->delete();

            foreach ($passport->evidence() as $evidence) {
                RoadPassportEvidenceModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'road_passport_id' => $model->id,
                    'type' => $evidence->type->value,
                    'subject_id' => $evidence->subjectId,
                    'course_id' => $evidence->courseId,
                    'details' => $evidence->details,
                    'occurred_at' => $evidence->occurredAt,
                ]);
            }
        });
    }

    public function findById(RoadPassportId $id): ?RoadPassport
    {
        $model = RoadPassportModel::query()->with(['historyEntries', 'evidenceEntries'])->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByUserId(string $userId): ?RoadPassport
    {
        $model = RoadPassportModel::query()->with(['historyEntries', 'evidenceEntries'])->where('user_id', $userId)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    private function toDomain(RoadPassportModel $model): RoadPassport
    {
        /** @var list<RoadPassportHistoryEntryModel> $historyModels */
        $historyModels = array_values($model->historyEntries->all());

        /** @var list<RoadPassportEvidenceModel> $evidenceModels */
        $evidenceModels = array_values($model->evidenceEntries->all());

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
            evidence: array_map(
                static function (RoadPassportEvidenceModel $entry): Evidence {
                    /** @var array<string, mixed> $details */
                    $details = $entry->getAttribute('details') ?? [];

                    return Evidence::create(
                        EvidenceType::from((string) $entry->getAttribute('type')),
                        (string) $entry->getAttribute('subject_id'),
                        (string) $entry->getAttribute('course_id'),
                        new DateTimeImmutable((string) $entry->getAttribute('occurred_at')),
                        $details,
                    );
                },
                $evidenceModels,
            ),
        );
    }
}
