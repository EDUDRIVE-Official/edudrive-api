<?php

declare(strict_types=1);

namespace Modules\Legal\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;
use Modules\Legal\Infrastructure\Persistence\Eloquent\Models\ConsentPolicyModel;

final class EloquentConsentPolicyRepository implements ConsentPolicyRepository
{
    public function save(ConsentPolicy $policy): void
    {
        ConsentPolicyModel::query()->updateOrCreate(
            ['id' => $policy->id()],
            [
                'key' => $policy->key()->value(),
                'version' => $policy->version(),
                'effective_at' => $policy->effectiveAt(),
            ],
        );
    }

    public function findCurrentByKey(PolicyKey $key): ?ConsentPolicy
    {
        $model = ConsentPolicyModel::query()
            ->where('key', $key->value())
            ->orderByDesc('version')
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<ConsentPolicy> */
    public function allCurrent(): array
    {
        $latestPerKey = ConsentPolicyModel::query()
            ->selectRaw('MAX(version) as version, "key"')
            ->groupBy('key')
            ->get();

        return array_values(
            $latestPerKey
                ->map(function (ConsentPolicyModel $row): ConsentPolicy {
                    $model = ConsentPolicyModel::query()
                        ->where('key', $row->getAttribute('key'))
                        ->where('version', $row->getAttribute('version'))
                        ->firstOrFail();

                    return $this->toDomain($model);
                })
                ->all(),
        );
    }

    private function toDomain(ConsentPolicyModel $model): ConsentPolicy
    {
        return ConsentPolicy::restore(
            id: (string) $model->getAttribute('id'),
            key: PolicyKey::fromString((string) $model->getAttribute('key')),
            version: (int) $model->getAttribute('version'),
            effectiveAt: $model->effective_at->toDateTimeImmutable(),
        );
    }
}
