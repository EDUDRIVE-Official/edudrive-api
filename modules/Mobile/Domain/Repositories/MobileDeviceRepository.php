<?php

declare(strict_types=1);

namespace Modules\Mobile\Domain\Repositories;

use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;

interface MobileDeviceRepository
{
    public function save(MobileDevice $device): void;

    public function findById(MobileDeviceId $id): ?MobileDevice;

    public function findByUserAndDeviceId(string $userId, string $deviceId): ?MobileDevice;

    /** @return list<MobileDevice> */
    public function findByUser(string $userId): array;

    /** @return list<MobileDevice> */
    public function findWithPushTokenByUser(string $userId): array;

    public function delete(MobileDeviceId $id): void;
}
