<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Entities;

use DateTimeImmutable;
use Modules\Identity\Domain\Exceptions\InvalidGuardianRelationship;

final class GuardianRelationship
{
    private function __construct(
        private readonly string $id,
        private readonly string $guardianUserId,
        private readonly string $minorUserId,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $revokedAt,
    ) {}

    public static function create(
        string $id,
        string $guardianUserId,
        string $minorUserId,
        ?DateTimeImmutable $occurredAt = null,
    ): self {
        if ($guardianUserId === $minorUserId) {
            throw InvalidGuardianRelationship::selfGuardianship();
        }

        return new self(
            id: $id,
            guardianUserId: $guardianUserId,
            minorUserId: $minorUserId,
            createdAt: $occurredAt ?? new DateTimeImmutable,
            revokedAt: null,
        );
    }

    public static function restore(
        string $id,
        string $guardianUserId,
        string $minorUserId,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $revokedAt,
    ): self {
        return new self(
            id: $id,
            guardianUserId: $guardianUserId,
            minorUserId: $minorUserId,
            createdAt: $createdAt,
            revokedAt: $revokedAt,
        );
    }

    public function revoke(DateTimeImmutable $at): void
    {
        if ($this->revokedAt !== null) {
            throw InvalidGuardianRelationship::alreadyRevoked();
        }

        $this->revokedAt = $at;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function guardianUserId(): string
    {
        return $this->guardianUserId;
    }

    public function minorUserId(): string
    {
        return $this->minorUserId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
