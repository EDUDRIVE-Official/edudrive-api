<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\UseCases;

use Modules\Mobile\Application\Commands\RemoveMobileDeviceCommand;
use Modules\Mobile\Application\Exceptions\MobileDeviceNotFound;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;

final readonly class RemoveMobileDeviceHandler
{
    public function __construct(private MobileDeviceRepository $devices) {}

    public function handle(RemoveMobileDeviceCommand $command): void
    {
        $device = $this->devices->findByUserAndDeviceId($command->userId, $command->deviceId);
        if ($device === null) {
            throw MobileDeviceNotFound::withDeviceId($command->deviceId);
        }

        $this->devices->delete($device->id());
    }
}
