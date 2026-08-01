<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(
        'Feature',
        '../modules/*/Tests/Unit',
        '../modules/*/Tests/Feature',
        '../modules/*/Tests/Integration',
    );

function actingAsAuthenticatedUser(): UserModel
{
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    Sanctum::actingAs($model);

    return $model;
}

/**
 * Like `actingAsAuthenticatedUser()`, but the acting user additionally holds
 * the `SuperAdmin` role, for endpoints gated behind a `permission:...`
 * middleware where `SuperAdmin` is the only role granted that permission
 * (per `RolePermissions`).
 */
function actingAsSuperAdminUser(): UserModel
{
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::SuperAdmin,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($model);

    return $model;
}
