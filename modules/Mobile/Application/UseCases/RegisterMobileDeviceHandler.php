<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Mobile\Application\Commands\RegisterMobileDeviceCommand;
use Modules\Mobile\Application\Exceptions\InvalidDevicePlatform;
use Modules\Mobile\Application\Responses\MobileDeviceResponse;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;

final readonly class RegisterMobileDeviceHandler
{
    public function __construct(private MobileDeviceRepository $devices) {}

    public function handle(RegisterMobileDeviceCommand $command): MobileDeviceResponse
    {
        $platform = DevicePlatform::tryFrom($command->platform);
        if ($platform === null) {
            throw InvalidDevicePlatform::withValue($command->platform);
        }

        $now = new DateTimeImmutable('now');
        $existing = $this->devices->findByUserAndDeviceId($command->userId, $command->deviceId);

        if ($existing !== null) {
            $existing->updateRegistration($platform, $command->pushToken, $command->appVersion, $now);
            $this->devices->save($existing);

            return MobileDeviceResponse::fromDevice($existing);
        }

        $device = MobileDevice::register(
            id: MobileDeviceId::fromString((string) Str::uuid()),
            userId: $command->userId,
            deviceId: $command->deviceId,
            platform: $platform,
            pushToken: $command->pushToken,
            appVersion: $command->appVersion,
            lastSeenAt: $now,
        );

        $this->devices->save($device);

        return MobileDeviceResponse::fromDevice($device);
    }
}
