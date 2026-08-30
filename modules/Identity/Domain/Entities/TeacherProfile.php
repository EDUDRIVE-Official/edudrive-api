<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Entities;

use DateTimeImmutable;

final class TeacherProfile
{
    private function __construct(
        private readonly string $userId,
        private ?string $specialties,
        private ?string $certifications,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(string $userId, ?DateTimeImmutable $occurredAt = null): self
    {
        return new self(
            userId: $userId,
            specialties: null,
            certifications: null,
            updatedAt: $occurredAt ?? new DateTimeImmutable,
        );
    }

    public static function restore(
        string $userId,
        ?string $specialties,
        ?string $certifications,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            userId: $userId,
            specialties: $specialties,
            certifications: $certifications,
            updatedAt: $updatedAt,
        );
    }

    public function update(
        ?string $specialties,
        ?string $certifications,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->specialties = $specialties;
        $this->certifications = $certifications;
        $this->updatedAt = $occurredAt;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function specialties(): ?string
    {
        return $this->specialties;
    }

    public function certifications(): ?string
    {
        return $this->certifications;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
