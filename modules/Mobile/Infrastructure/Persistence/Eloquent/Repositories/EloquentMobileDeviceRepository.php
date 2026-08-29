<?php

declare(strict_types=1);

namespace Modules\Mobile\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;
use Modules\Mobile\Infrastructure\Persistence\Eloquent\Models\MobileDeviceModel;

final readonly class EloquentMobileDeviceRepository implements MobileDeviceRepository
{
    public function save(MobileDevice $device): void
    {
        MobileDeviceModel::query()->updateOrCreate(
            ['id' => $device->id()->value()],
            [
                'user_id' => $device->userId(),
                'device_id' => $device->deviceId(),
                'platform' => $device->platform()->value,
                'push_token' => $device->pushToken(),
                'app_version' => $device->appVersion(),
                'last_seen_at' => $device->lastSeenAt(),
            ],
        );
    }

    public function findById(MobileDeviceId $id): ?MobileDevice
    {
        $model = MobileDeviceModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByUserAndDeviceId(string $userId, string $deviceId): ?MobileDevice
    {
        $model = MobileDeviceModel::query()
            ->where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<MobileDevice> */
    public function findByUser(string $userId): array
    {
        return array_values(
            MobileDeviceModel::query()
                ->where('user_id', $userId)
                ->orderBy('created_at')
                ->get()
                ->map(fn (MobileDeviceModel $model): MobileDevice => $this->toDomain($model))
                ->all(),
        );
    }

    /** @return list<MobileDevice> */
    public function findWithPushTokenByUser(string $userId): array
    {
        return array_values(
            MobileDeviceModel::query()
                ->where('user_id', $userId)
                ->whereNotNull('push_token')
                ->get()
                ->map(fn (MobileDeviceModel $model): MobileDevice => $this->toDomain($model))
                ->all(),
        );
    }

    public function delete(MobileDeviceId $id): void
    {
        MobileDeviceModel::query()->where('id', $id->value())->delete();
    }

    private function toDomain(MobileDeviceModel $model): MobileDevice
    {
        return MobileDevice::restore(
            id: MobileDeviceId::fromString((string) $model->getAttribute('id')),
            userId: (string) $model->getAttribute('user_id'),
            deviceId: (string) $model->getAttribute('device_id'),
            platform: DevicePlatform::from((string) $model->getAttribute('platform')),
            pushToken: $model->getAttribute('push_token') === null ? null : (string) $model->getAttribute('push_token'),
            appVersion: (string) $model->getAttribute('app_version'),
            lastSeenAt: new DateTimeImmutable((string) $model->getAttribute('last_seen_at')),
            createdAt: new DateTimeImmutable((string) $model->getAttribute('created_at')),
        );
    }
}
