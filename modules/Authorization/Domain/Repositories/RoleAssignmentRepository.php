<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Repositories;

use Modules\Authorization\Domain\Entities\RoleAssignment;

interface RoleAssignmentRepository
{
    public function save(RoleAssignment $assignment): void;

    /**
     * @return list<RoleAssignment>
     */
    public function findByUserId(string $userId): array;
}
