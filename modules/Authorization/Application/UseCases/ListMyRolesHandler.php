<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\UseCases;

use Modules\Authorization\Application\Queries\ListMyRolesQuery;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;

final readonly class ListMyRolesHandler
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
    ) {}

    /**
     * @return list<RoleAssignmentResponse>
     */
    public function handle(ListMyRolesQuery $query): array
    {
        return array_map(
            static fn (RoleAssignment $assignment): RoleAssignmentResponse => RoleAssignmentResponse::fromRoleAssignment($assignment),
            $this->assignments->findByUserId($query->userId),
        );
    }
}
