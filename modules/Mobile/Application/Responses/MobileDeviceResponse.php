<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Responses;

use DateTimeInterface;
use Modules\Mobile\Domain\Aggregates\MobileDevice;

final readonly class MobileDeviceResponse
{
    public function __construct(
        public string $id,
        public string $deviceId,
        public string $platform,
        public bool $hasPushToken,
        public string $appVersion,
        public string $lastSeenAt,
        public string $createdAt,
    ) {}

    public static function fromDevice(MobileDevice $device): self
    {
        return new self(
            id: $device->id()->value(),
            deviceId: $device->deviceId(),
            platform: $device->platform()->value,
            hasPushToken: $device->hasPushToken(),
            appVersion: $device->appVersion(),
            lastSeenAt: $device->lastSeenAt()->format(DateTimeInterface::ATOM),
            createdAt: $device->createdAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->deviceId,
            'platform' => $this->platform,
            'has_push_token' => $this->hasPushToken,
            'app_version' => $this->appVersion,
            'last_seen_at' => $this->lastSeenAt,
            'created_at' => $this->createdAt,
        ];
    }
}
