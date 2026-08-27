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
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

function persistedOrganizationForUpdate(): Organization
{
    $organization = Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );
    app(OrganizationRepository::class)->save($organization);

    return $organization;
}

it('consulta el detalle de una organización', function (): void {
    actingAsAuthenticatedUser();
    $organization = persistedOrganizationForUpdate();

    getJson("/api/v1/organizations/{$organization->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $organization->id()->value())
        ->assertJsonPath('data.name', 'Escuela de Manejo EDUDRIVE');
});

it('rechaza consultar el detalle sin autenticación', function (): void {
    $organization = persistedOrganizationForUpdate();

    getJson("/api/v1/organizations/{$organization->id()->value()}")->assertUnauthorized();
});

it('actualiza el nombre de una organización con el permiso organizations.manage', function (): void {
    actingAsSuperAdminUser();
    $organization = persistedOrganizationForUpdate();

    putJson("/api/v1/organizations/{$organization->id()->value()}", [
        'name' => 'Escuela de Manejo Costa Rica',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Escuela de Manejo Costa Rica');
});

it('rechaza actualizar una organización sin el permiso organizations.manage', function (): void {
    $organization = persistedOrganizationForUpdate();
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    putJson("/api/v1/organizations/{$organization->id()->value()}", [
        'name' => 'Intento sin permiso',
    ])->assertForbidden();
});

it('rechaza actualizar una organizacion inexistente', function (): void {
    actingAsSuperAdminUser();

    putJson('/api/v1/organizations/'.((string) Str::uuid()), [
        'name' => 'Organización inexistente',
    ])
        ->assertStatus(404)
        ->assertJsonPath('code', 'ORGANIZATION_NOT_FOUND');
});
