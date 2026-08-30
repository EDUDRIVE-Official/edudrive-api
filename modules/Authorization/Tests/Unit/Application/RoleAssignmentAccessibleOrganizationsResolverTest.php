<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Services\RoleAssignmentAccessibleOrganizationsResolver;

final class InMemoryRoleAssignmentRepositoryForAccessibleOrganizations implements RoleAssignmentRepository
{
    /** @var list<RoleAssignment> */
    public array $assignments = [];

    public function save(RoleAssignment $assignment): void
    {
        $this->assignments[] = $assignment;
    }

    /** @return list<RoleAssignment> */
    public function findByUserId(string $userId): array
    {
        return array_values(array_filter(
            $this->assignments,
            fn (RoleAssignment $assignment): bool => $assignment->userId() === $userId,
        ));
    }
}

it('devuelve null cuando el usuario tiene una asignacion global que otorga el permiso', function (): void {
    $assignments = new InMemoryRoleAssignmentRepositoryForAccessibleOrganizations;
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-1',
        userId: 'user-1',
        role: Role::SuperAdmin,
        organizationId: null,
    ));

    $resolver = new RoleAssignmentAccessibleOrganizationsResolver($assignments);

    expect($resolver->resolveForPermission('user-1', Permission::ViewReports))->toBeNull();
});

it('devuelve las organizaciones de las asignaciones que otorgan el permiso', function (): void {
    $assignments = new InMemoryRoleAssignmentRepositoryForAccessibleOrganizations;
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-1',
        userId: 'user-1',
        role: Role::InstitutionalAdmin,
        organizationId: 'org-1',
    ));
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-2',
        userId: 'user-1',
        role: Role::InstitutionalAdmin,
        organizationId: 'org-2',
    ));

    $resolver = new RoleAssignmentAccessibleOrganizationsResolver($assignments);

    expect($resolver->resolveForPermission('user-1', Permission::ViewReports))->toBe(['org-1', 'org-2']);
});

it('ignora asignaciones cuyo rol no otorga el permiso', function (): void {
    $assignments = new InMemoryRoleAssignmentRepositoryForAccessibleOrganizations;
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-1',
        userId: 'user-1',
        role: Role::InstitutionalAdmin,
        organizationId: 'org-1',
    ));
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-2',
        userId: 'user-1',
        role: Role::Student,
        organizationId: 'org-2',
    ));

    $resolver = new RoleAssignmentAccessibleOrganizationsResolver($assignments);

    expect($resolver->resolveForPermission('user-1', Permission::ViewReports))->toBe(['org-1']);
});

it('no duplica organizaciones repetidas entre varias asignaciones', function (): void {
    $assignments = new InMemoryRoleAssignmentRepositoryForAccessibleOrganizations;
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-1',
        userId: 'user-1',
        role: Role::InstitutionalAdmin,
        organizationId: 'org-1',
    ));
    $assignments->save(RoleAssignment::assign(
        id: 'assignment-2',
        userId: 'user-1',
        role: Role::InstitutionalAdmin,
        organizationId: 'org-1',
    ));

    $resolver = new RoleAssignmentAccessibleOrganizationsResolver($assignments);

    expect($resolver->resolveForPermission('user-1', Permission::ViewReports))->toBe(['org-1']);
});
