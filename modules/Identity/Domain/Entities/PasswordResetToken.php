<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Entities;

use DateInterval;
use DateTimeImmutable;
use Modules\Identity\Domain\ValueObjects\Email;

final class PasswordResetToken
{
    private const TTL_MINUTES = 60;

    private function __construct(
        private readonly Email $email,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function issue(
        Email $email,
        string $tokenHash,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            email: $email,
            tokenHash: $tokenHash,
            createdAt: $createdAt ?? new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        Email $email,
        string $tokenHash,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            email: $email,
            tokenHash: $tokenHash,
            createdAt: $createdAt,
        );
    }

    public function matchesHash(string $tokenHash): bool
    {
        return hash_equals($this->tokenHash, $tokenHash);
    }

    public function isExpired(DateTimeImmutable $asOf): bool
    {
        return $asOf > $this->createdAt->add(new DateInterval(sprintf('PT%dM', self::TTL_MINUTES)));
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
