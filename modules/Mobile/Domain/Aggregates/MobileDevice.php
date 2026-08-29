<?php

declare(strict_types=1);

namespace Modules\Mobile\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;

final class MobileDevice
{
    private function __construct(
        private MobileDeviceId $id,
        private string $userId,
        private string $deviceId,
        private DevicePlatform $platform,
        private ?string $pushToken,
        private string $appVersion,
        private DateTimeImmutable $lastSeenAt,
        private DateTimeImmutable $createdAt,
    ) {}

    public static function register(
        MobileDeviceId $id,
        string $userId,
        string $deviceId,
        DevicePlatform $platform,
        ?string $pushToken,
        string $appVersion,
        ?DateTimeImmutable $lastSeenAt = null,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        $at = $lastSeenAt ?? new DateTimeImmutable('now');

        return new self(
            $id,
            $userId,
            $deviceId,
            $platform,
            $pushToken,
            $appVersion,
            $at,
            $createdAt ?? $at,
        );
    }

    public static function restore(
        MobileDeviceId $id,
        string $userId,
        string $deviceId,
        DevicePlatform $platform,
        ?string $pushToken,
        string $appVersion,
        DateTimeImmutable $lastSeenAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $userId, $deviceId, $platform, $pushToken, $appVersion, $lastSeenAt, $createdAt);
    }

    public function updateRegistration(
        DevicePlatform $platform,
        ?string $pushToken,
        string $appVersion,
        DateTimeImmutable $at,
    ): void {
        $this->platform = $platform;
        $this->pushToken = $pushToken;
        $this->appVersion = $appVersion;
        $this->lastSeenAt = $at;
    }

    public function hasPushToken(): bool
    {
        return $this->pushToken !== null;
    }

    public function id(): MobileDeviceId
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function deviceId(): string
    {
        return $this->deviceId;
    }

    public function platform(): DevicePlatform
    {
        return $this->platform;
    }

    public function pushToken(): ?string
    {
        return $this->pushToken;
    }

    public function appVersion(): string
    {
        return $this->appVersion;
    }

    public function lastSeenAt(): DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
