<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Responses;

use Modules\Authorization\Domain\Entities\RoleAssignment;

final readonly class RoleAssignmentResponse
{
    private function __construct(
        public string $id,
        public string $userId,
        public string $role,
        public ?string $organizationId,
    ) {}

    public static function fromRoleAssignment(RoleAssignment $assignment): self
    {
        return new self(
            id: $assignment->id(),
            userId: $assignment->userId(),
            role: $assignment->role()->value,
            organizationId: $assignment->organizationId(),
        );
    }

    /**
     * @return array{id: string, user_id: string, role: string, organization_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'role' => $this->role,
            'organization_id' => $this->organizationId,
        ];
    }
}
