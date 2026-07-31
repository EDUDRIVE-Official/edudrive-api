<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\UseCases;

use Illuminate\Support\Str;
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
    ) {}

    public function handle(
        AssignRoleCommand $command,
    ): RoleAssignmentResponse {
        if ($this->users->findById($command->userId) === null) {
            throw UserNotFound::withId($command->userId);
        }

        $assignment = RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $command->userId,
            role: Role::from($command->role),
            organizationId: $command->organizationId,
        );

        $this->assignments->save($assignment);

        return RoleAssignmentResponse::fromRoleAssignment($assignment);
    }
}
