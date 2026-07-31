<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;

final class EloquentRoleAssignmentRepository implements RoleAssignmentRepository
{
    public function save(RoleAssignment $assignment): void
    {
        RoleAssignmentModel::query()->updateOrCreate(
            ['id' => $assignment->id()],
            [
                'user_id' => $assignment->userId(),
                'role' => $assignment->role()->value,
                'organization_id' => $assignment->organizationId(),
                'assigned_at' => $assignment->assignedAt(),
            ],
        );
    }

    /**
     * @return list<RoleAssignment>
     */
    public function findByUserId(string $userId): array
    {
        $assignments = RoleAssignmentModel::query()
            ->where('user_id', $userId)
            ->orderBy('assigned_at')
            ->orderBy('id')
            ->get()
            ->map(
                static fn (RoleAssignmentModel $model): RoleAssignment => RoleAssignment::assign(
                    id: (string) $model->getAttribute('id'),
                    userId: (string) $model->getAttribute('user_id'),
                    role: Role::from((string) $model->getAttribute('role')),
                    organizationId: $model->getAttribute('organization_id') === null
                        ? null
                        : (string) $model->getAttribute('organization_id'),
                    assignedAt: $model->getAttribute('assigned_at'),
                ),
            )
            ->all();

        return array_values($assignments);
    }
}
