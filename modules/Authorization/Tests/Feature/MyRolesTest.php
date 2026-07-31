<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\getJson;

use Tests\TestCase;

it('lista los roles del usuario autenticado', function (): void {
    /** @var TestCase $this */
    $userRepository = $this->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente EDUDRIVE',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $userRepository->save($user);

    $this->app->make(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    getJson('/api/v1/authorization/me/roles')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role', 'teacher');
});

it('devuelve una lista vacía cuando el usuario no tiene roles asignados', function (): void {
    actingAsAuthenticatedUser();

    getJson('/api/v1/authorization/me/roles')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rechaza la consulta de roles sin autenticación', function (): void {
    getJson('/api/v1/authorization/me/roles')->assertUnauthorized();
});
