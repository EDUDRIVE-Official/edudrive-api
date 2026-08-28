<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Entities;

use DateTimeImmutable;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final class UserConsent
{
    private function __construct(
        private string $id,
        private string $userId,
        private PolicyKey $policyKey,
        private int $policyVersion,
        private DateTimeImmutable $acceptedAt,
    ) {}

    public static function accept(
        string $id,
        string $userId,
        PolicyKey $policyKey,
        int $policyVersion,
        ?DateTimeImmutable $acceptedAt = null,
    ): self {
        return new self($id, $userId, $policyKey, $policyVersion, $acceptedAt ?? new DateTimeImmutable('now'));
    }

    public static function restore(
        string $id,
        string $userId,
        PolicyKey $policyKey,
        int $policyVersion,
        DateTimeImmutable $acceptedAt,
    ): self {
        return new self($id, $userId, $policyKey, $policyVersion, $acceptedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function policyKey(): PolicyKey
    {
        return $this->policyKey;
    }

    public function policyVersion(): int
    {
        return $this->policyVersion;
    }

    public function acceptedAt(): DateTimeImmutable
    {
        return $this->acceptedAt;
    }
}
