<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Entities;

use DateTimeImmutable;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Exceptions\InvalidUserName;
use Modules\Identity\Domain\ValueObjects\Email;

final class User
{
    private function __construct(
        private readonly string $id,
        private string $name,
        private Email $email,
        private string $passwordHash,
        private UserStatus $status,
        private ?DateTimeImmutable $emailVerifiedAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $lastLoginAt,
        private ?DateTimeImmutable $dateOfBirth,
    ) {}

    public static function register(
        string $id,
        string $name,
        Email $email,
        string $passwordHash,
        ?DateTimeImmutable $registeredAt = null,
        ?DateTimeImmutable $dateOfBirth = null,
    ): self {
        $normalizedName = self::normalizeName($name);
        $registeredAt ??= new DateTimeImmutable;

        return new self(
            id: $id,
            name: $normalizedName,
            email: $email,
            passwordHash: $passwordHash,
            status: UserStatus::Pending,
            emailVerifiedAt: null,
            createdAt: $registeredAt,
            updatedAt: $registeredAt,
            lastLoginAt: null,
            dateOfBirth: $dateOfBirth,
        );
    }

    public static function reconstitute(
        string $id,
        string $name,
        Email $email,
        string $passwordHash,
        UserStatus $status,
        ?DateTimeImmutable $emailVerifiedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $lastLoginAt = null,
        ?DateTimeImmutable $dateOfBirth = null,
    ): self {
        return new self(
            id: $id,
            name: self::normalizeName($name),
            email: $email,
            passwordHash: $passwordHash,
            status: $status,
            emailVerifiedAt: $emailVerifiedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            lastLoginAt: $lastLoginAt,
            dateOfBirth: $dateOfBirth,
        );
    }

    public function activate(DateTimeImmutable $occurredAt): void
    {
        $this->status = UserStatus::Active;
        $this->emailVerifiedAt ??= $occurredAt;
        $this->updatedAt = $occurredAt;
    }

    public function deactivate(DateTimeImmutable $occurredAt): void
    {
        $this->status = UserStatus::Inactive;
        $this->updatedAt = $occurredAt;
    }

    public function lock(DateTimeImmutable $occurredAt): void
    {
        $this->status = UserStatus::Locked;
        $this->updatedAt = $occurredAt;
    }

    public function rename(string $name, DateTimeImmutable $occurredAt): void
    {
        $this->name = self::normalizeName($name);
        $this->updatedAt = $occurredAt;
    }

    public function changeEmail(
        Email $email,
        DateTimeImmutable $occurredAt,
    ): void {
        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->status = UserStatus::Pending;
        $this->updatedAt = $occurredAt;
    }

    public function changePasswordHash(
        string $passwordHash,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->passwordHash = $passwordHash;
        $this->updatedAt = $occurredAt;
    }

    public function recordLogin(DateTimeImmutable $occurredAt): void
    {
        $this->lastLoginAt = $occurredAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function lastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function dateOfBirth(): ?DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function isMinor(?DateTimeImmutable $asOf = null): bool
    {
        if ($this->dateOfBirth === null) {
            return false;
        }

        $asOf ??= new DateTimeImmutable;

        return $this->dateOfBirth->diff($asOf)->y < 18;
    }

    private static function normalizeName(string $name): string
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw InvalidUserName::empty();
        }

        if (mb_strlen($normalizedName) > 150) {
            throw InvalidUserName::tooLong();
        }

        return $normalizedName;
    }
}
