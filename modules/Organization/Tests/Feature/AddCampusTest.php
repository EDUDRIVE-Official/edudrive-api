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

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

use Tests\TestCase;

it('agrega una sede a una organización existente', function (): void {
    $organizations = app(OrganizationRepository::class);

    $organizationId = OrganizationId::fromString((string) Str::uuid());

    $organizations->save(Organization::create(
        id: $organizationId,
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    ));

    actingAsSuperAdminUser();

    postJson("/api/v1/organizations/{$organizationId->value()}/campuses", [
        'name' => 'Sede Cabo Velas',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sede Cabo Velas');

    assertDatabaseHas('organization_campuses', [
        'organization_id' => $organizationId->value(),
        'name' => 'Sede Cabo Velas',
    ]);
});

it('devuelve 404 al agregar una sede a una organización inexistente', function (): void {
    actingAsSuperAdminUser();

    postJson('/api/v1/organizations/'.((string) Str::uuid()).'/campuses', [
        'name' => 'Sede Fantasma',
    ])->assertNotFound();
});

it('rechaza datos obligatorios faltantes', function (): void {
    $organizations = app(OrganizationRepository::class);

    $organizationId = OrganizationId::fromString((string) Str::uuid());

    $organizations->save(Organization::create(
        id: $organizationId,
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    ));

    actingAsSuperAdminUser();

    postJson("/api/v1/organizations/{$organizationId->value()}/campuses", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/organizations/'.((string) Str::uuid()).'/campuses', [
        'name' => 'Sede Sin Autenticación',
    ])->assertUnauthorized();
});

it('rechaza agregar una sede a un usuario sin el permiso organizations.manage', function (): void {
    /** @var TestCase $this */
    $organizations = app(OrganizationRepository::class);

    $organizationId = OrganizationId::fromString((string) Str::uuid());

    $organizations->save(Organization::create(
        id: $organizationId,
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    ));

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

    postJson("/api/v1/organizations/{$organizationId->value()}/campuses", [
        'name' => 'Sede sin permiso',
    ])->assertForbidden();
});
