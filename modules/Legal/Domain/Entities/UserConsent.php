<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Entities;

use DateTimeImmutable;
use Modules\Legal\Domain\Exceptions\ConsentAlreadyRevoked;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final class UserConsent
{
    private function __construct(
        private string $id,
        private string $userId,
        private PolicyKey $policyKey,
        private int $policyVersion,
        private DateTimeImmutable $acceptedAt,
        private ?string $guardianDeclaration,
        private ?DateTimeImmutable $revokedAt,
    ) {}

    public static function accept(
        string $id,
        string $userId,
        PolicyKey $policyKey,
        int $policyVersion,
        ?DateTimeImmutable $acceptedAt = null,
        ?string $guardianDeclaration = null,
    ): self {
        return new self(
            $id,
            $userId,
            $policyKey,
            $policyVersion,
            $acceptedAt ?? new DateTimeImmutable('now'),
            $guardianDeclaration,
            null,
        );
    }

    public static function restore(
        string $id,
        string $userId,
        PolicyKey $policyKey,
        int $policyVersion,
        DateTimeImmutable $acceptedAt,
        ?string $guardianDeclaration = null,
        ?DateTimeImmutable $revokedAt = null,
    ): self {
        return new self($id, $userId, $policyKey, $policyVersion, $acceptedAt, $guardianDeclaration, $revokedAt);
    }

    public function revoke(DateTimeImmutable $at): void
    {
        if ($this->revokedAt !== null) {
            throw ConsentAlreadyRevoked::create();
        }

        $this->revokedAt = $at;
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

    public function guardianDeclaration(): ?string
    {
        return $this->guardianDeclaration;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }
}
