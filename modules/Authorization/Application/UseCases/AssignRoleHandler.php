<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Application\Exceptions\UserNotFound;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class AssignRoleHandler
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
        private UserRepository $users,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(
        AssignRoleCommand $command,
    ): RoleAssignmentResponse {
        if ($this->users->findById($command->userId) === null) {
            throw UserNotFound::withId($command->userId);
        }

        $role = Role::from($command->role);

        $existing = $this->findExisting($command->userId, $role, $command->organizationId);
        if ($existing !== null) {
            return RoleAssignmentResponse::fromRoleAssignment($existing);
        }

        $assignment = RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $command->userId,
            role: $role,
            organizationId: $command->organizationId,
        );

        $this->assignments->save($assignment);

        $this->auditLogger->log(
            new AuditEntry(
                action: 'authorization.role_assigned',
                userId: $command->actorId,
                entity: 'RoleAssignment',
                entityId: $assignment->id(),
                metadata: [
                    'target_user_id' => $command->userId,
                    'role' => $command->role,
                    'organization_id' => $command->organizationId,
                ],
            ),
        );

        return RoleAssignmentResponse::fromRoleAssignment($assignment);
    }

    private function findExisting(string $userId, Role $role, ?string $organizationId): ?RoleAssignment
    {
        foreach ($this->assignments->findByUserId($userId) as $assignment) {
            if ($assignment->role() === $role && $assignment->organizationId() === $organizationId) {
                return $assignment;
            }
        }

        return null;
    }
}
