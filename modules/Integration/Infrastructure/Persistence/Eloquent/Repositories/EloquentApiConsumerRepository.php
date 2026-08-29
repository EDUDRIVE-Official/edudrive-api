<?php

declare(strict_types=1);

namespace Modules\Integration\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Enums\ApiConsumerStatus;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerHistoryEntry;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;
use Modules\Integration\Infrastructure\Persistence\Eloquent\Models\ApiConsumerHistoryEntryModel;
use Modules\Integration\Infrastructure\Persistence\Eloquent\Models\ApiConsumerModel;

final readonly class EloquentApiConsumerRepository implements ApiConsumerRepository
{
    public function save(ApiConsumer $consumer): void
    {
        DB::transaction(function () use ($consumer): void {
            $model = ApiConsumerModel::query()->updateOrCreate(
                ['id' => $consumer->id()->value()],
                [
                    'name' => $consumer->name(),
                    'scopes' => $consumer->scopes(),
                    'status' => $consumer->status()->value,
                    'integration_key_hash' => $consumer->integrationKey()->hash(),
                    'expires_at' => $consumer->expiresAt(),
                    'issued_at' => $consumer->createdAt(),
                ],
            );

            $model->historyEntries()->delete();

            foreach ($consumer->history() as $entry) {
                ApiConsumerHistoryEntryModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'api_consumer_id' => $model->id,
                    'from_status' => $entry->fromStatus->value,
                    'to_status' => $entry->toStatus->value,
                    'reason' => $entry->reason,
                    'occurred_at' => $entry->occurredAt,
                ]);
            }
        });
    }

    public function findById(ApiConsumerId $id): ?ApiConsumer
    {
        $model = ApiConsumerModel::query()->with('historyEntries')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?ApiConsumer
    {
        $model = ApiConsumerModel::query()->with('historyEntries')
            ->where('integration_key_hash', $integrationKeyHash)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<ApiConsumer> */
    public function all(): array
    {
        return array_values(
            ApiConsumerModel::query()->with('historyEntries')
                ->orderBy('issued_at')
                ->get()
                ->map(fn (ApiConsumerModel $model): ApiConsumer => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(ApiConsumerModel $model): ApiConsumer
    {
        /** @var list<ApiConsumerHistoryEntryModel> $historyModels */
        $historyModels = array_values($model->historyEntries->all());

        $expiresAt = $model->getAttribute('expires_at');

        /** @var list<string> $scopes */
        $scopes = $model->getAttribute('scopes') ?? [];

        return ApiConsumer::restore(
            id: ApiConsumerId::fromString((string) $model->getAttribute('id')),
            name: (string) $model->getAttribute('name'),
            scopes: $scopes,
            status: ApiConsumerStatus::from((string) $model->getAttribute('status')),
            integrationKey: IntegrationKey::fromHash((string) $model->getAttribute('integration_key_hash')),
            expiresAt: $expiresAt === null ? null : new DateTimeImmutable((string) $expiresAt),
            createdAt: new DateTimeImmutable((string) $model->getAttribute('issued_at')),
            history: array_map(
                static fn (ApiConsumerHistoryEntryModel $entry): ApiConsumerHistoryEntry => ApiConsumerHistoryEntry::restore(
                    ApiConsumerStatus::from((string) $entry->getAttribute('from_status')),
                    ApiConsumerStatus::from((string) $entry->getAttribute('to_status')),
                    new DateTimeImmutable((string) $entry->getAttribute('occurred_at')),
                    $entry->getAttribute('reason') === null ? null : (string) $entry->getAttribute('reason'),
                ),
                $historyModels,
            ),
        );
    }
}
