<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedRoleAssignmentUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera las asignaciones de rol de un usuario', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(RoleAssignmentRepository::class);

    $userId = persistedRoleAssignmentUserId();

    $repository->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $userId,
        role: Role::Teacher,
        organizationId: null,
        assignedAt: new DateTimeImmutable('2026-01-01 10:00:00'),
    ));

    $repository->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $userId,
        role: Role::Student,
        organizationId: (string) Str::uuid(),
        assignedAt: new DateTimeImmutable('2026-01-01 10:05:00'),
    ));

    $assignments = $repository->findByUserId($userId);

    expect($assignments)->toHaveCount(2)
        ->and($assignments[0]->role())->toBe(Role::Teacher)
        ->and($assignments[1]->role())->toBe(Role::Student);
});

it('no devuelve asignaciones de otros usuarios', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(RoleAssignmentRepository::class);

    $repository->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: persistedRoleAssignmentUserId(),
        role: Role::Student,
        organizationId: null,
    ));

    expect($repository->findByUserId(persistedRoleAssignmentUserId()))->toBe([]);
});
