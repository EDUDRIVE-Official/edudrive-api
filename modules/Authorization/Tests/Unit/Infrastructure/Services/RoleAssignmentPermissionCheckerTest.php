<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Services\RoleAssignmentPermissionChecker;

it('confirma un permiso otorgado por alguno de los roles del usuario', function (): void {
    $repository = new class implements RoleAssignmentRepository
    {
        public function save(RoleAssignment $assignment): void {}

        public function findByUserId(string $userId): array
        {
            return [
                RoleAssignment::assign(
                    id: 'assignment-1',
                    userId: $userId,
                    role: Role::Teacher,
                    organizationId: null,
                ),
            ];
        }
    };

    $checker = new RoleAssignmentPermissionChecker($repository);

    expect($checker->userHasPermission('user-1', Permission::ViewOrganizations))->toBeTrue()
        ->and($checker->userHasPermission('user-1', Permission::ManageOrganizations))->toBeFalse();
});

it('niega un permiso cuando el usuario no tiene ninguna asignación', function (): void {
    $repository = new class implements RoleAssignmentRepository
    {
        public function save(RoleAssignment $assignment): void {}

        public function findByUserId(string $userId): array
        {
            return [];
        }
    };

    $checker = new RoleAssignmentPermissionChecker($repository);

    expect($checker->userHasPermission('user-1', Permission::ViewOrganizations))->toBeFalse();
});

it('confirma un permiso cuando una asignación posterior lo otorga aunque la primera no lo haga', function (): void {
    $repository = new class implements RoleAssignmentRepository
    {
        public function save(RoleAssignment $assignment): void {}

        public function findByUserId(string $userId): array
        {
            return [
                RoleAssignment::assign(
                    id: 'assignment-1',
                    userId: $userId,
                    role: Role::Teacher,
                    organizationId: null,
                ),
                RoleAssignment::assign(
                    id: 'assignment-2',
                    userId: $userId,
                    role: Role::SuperAdmin,
                    organizationId: null,
                ),
            ];
        }
    };

    $checker = new RoleAssignmentPermissionChecker($repository);

    expect($checker->userHasPermission('user-1', Permission::ManageOrganizations))->toBeTrue();
});
