<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Entities;

use DateTimeImmutable;

final class StudentProfile
{
    private function __construct(
        private readonly string $userId,
        private ?string $educationLevel,
        private ?string $accessibilityNeeds,
        private ?string $learningPreferences,
        private DateTimeImmutable $updatedAt,
        private ?string $learningPurpose = null,
    ) {}

    public static function create(string $userId, ?DateTimeImmutable $occurredAt = null): self
    {
        return new self(
            userId: $userId,
            educationLevel: null,
            accessibilityNeeds: null,
            learningPreferences: null,
            updatedAt: $occurredAt ?? new DateTimeImmutable,
        );
    }

    public static function restore(
        string $userId,
        ?string $educationLevel,
        ?string $accessibilityNeeds,
        ?string $learningPreferences,
        DateTimeImmutable $updatedAt,
        ?string $learningPurpose = null,
    ): self {
        return new self(
            userId: $userId,
            educationLevel: $educationLevel,
            accessibilityNeeds: $accessibilityNeeds,
            learningPreferences: $learningPreferences,
            updatedAt: $updatedAt,
            learningPurpose: $learningPurpose,
        );
    }

    public function update(
        ?string $educationLevel,
        ?string $accessibilityNeeds,
        ?string $learningPreferences,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->educationLevel = $educationLevel;
        $this->accessibilityNeeds = $accessibilityNeeds;
        $this->learningPreferences = $learningPreferences;
        $this->updatedAt = $occurredAt;
    }

    public function learningPurpose(): ?string
    {
        return $this->learningPurpose;
    }

    public function choosePurpose(?string $purpose): void
    {
        if ($purpose !== null && ! in_array($purpose, ['mobility', 'auto', 'motorcycle'], true)) {
            throw new \InvalidArgumentException('Propósito de aprendizaje inválido.');
        }
        $this->learningPurpose = $purpose;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function educationLevel(): ?string
    {
        return $this->educationLevel;
    }

    public function accessibilityNeeds(): ?string
    {
        return $this->accessibilityNeeds;
    }

    public function learningPreferences(): ?string
    {
        return $this->learningPreferences;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
