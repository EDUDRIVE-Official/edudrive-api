<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\Admin\Infrastructure\Persistence\Eloquent\Models\SystemSettingModel;

final readonly class EloquentSystemSettingRepository implements SystemSettingRepository
{
    public function save(SystemSetting $setting): void
    {
        SystemSettingModel::query()->updateOrCreate(
            ['key' => $setting->key()->value()],
            [
                'value' => $setting->value(),
                'changed_at' => $setting->changedAt(),
            ],
        );
    }

    public function findByKey(SystemSettingKey $key): ?SystemSetting
    {
        $model = SystemSettingModel::query()->where('key', $key->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<SystemSetting> */
    public function all(): array
    {
        return array_values(
            SystemSettingModel::query()
                ->orderBy('key')
                ->get()
                ->map(fn (SystemSettingModel $model): SystemSetting => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(SystemSettingModel $model): SystemSetting
    {
        return SystemSetting::restore(
            key: SystemSettingKey::fromString((string) $model->getAttribute('key')),
            value: (string) $model->getAttribute('value'),
            changedAt: new DateTimeImmutable((string) $model->getAttribute('changed_at')),
        );
    }
}
