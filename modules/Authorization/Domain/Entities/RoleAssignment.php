<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Entities;

use DateTimeImmutable;
use Modules\Authorization\Domain\Enums\Role;

final class RoleAssignment
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly Role $role,
        private readonly ?string $organizationId,
        private readonly DateTimeImmutable $assignedAt,
    ) {}

    public static function assign(
        string $id,
        string $userId,
        Role $role,
        ?string $organizationId,
        ?DateTimeImmutable $assignedAt = null,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            role: $role,
            organizationId: $organizationId,
            assignedAt: $assignedAt ?? new DateTimeImmutable,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function organizationId(): ?string
    {
        return $this->organizationId;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
