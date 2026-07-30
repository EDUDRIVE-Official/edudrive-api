<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;

it('crea una asignación de rol con fecha por defecto', function (): void {
    $assignment = RoleAssignment::assign(
        id: '01981a64-8300-7b1d-b442-764ea7f91600',
        userId: '01981a64-8300-7b1d-b442-764ea7f91601',
        role: Role::Teacher,
        organizationId: null,
    );

    expect($assignment->role())->toBe(Role::Teacher)
        ->and($assignment->userId())->toBe('01981a64-8300-7b1d-b442-764ea7f91601')
        ->and($assignment->organizationId())->toBeNull()
        ->and($assignment->assignedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('acepta una organización asociada a la asignación', function (): void {
    $assignment = RoleAssignment::assign(
        id: '01981a64-8300-7b1d-b442-764ea7f91602',
        userId: '01981a64-8300-7b1d-b442-764ea7f91601',
        role: Role::InstitutionalAdmin,
        organizationId: '01981a64-8300-7b1d-b442-764ea7f91603',
    );

    expect($assignment->organizationId())->toBe('01981a64-8300-7b1d-b442-764ea7f91603');
});
