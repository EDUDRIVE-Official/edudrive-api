<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
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
use Tests\TestCase;

function persistedReportOrganization(): Organization
{
    $organization = Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Organización de reportes '.Str::random(4)),
        type: OrganizationType::DrivingSchool,
    );
    app(OrganizationRepository::class)->save($organization);

    return $organization;
}

function actingAsRoleInOrganization(Role $role, string $organizationId): UserModel
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario institucional de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $user->id(),
        role: $role,
        organizationId: $organizationId,
    ));

    Sanctum::actingAs($model);

    return $model;
}

it('consulta los cuatro indicadores institucionales con el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/academic/reports/organizations/participation')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/organizations/completion')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/organizations/performance')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/organizations/adoption')->assertOk()->assertJsonStructure(['data']);
});

it('rechaza los indicadores institucionales sin reports.view aunque el rol sea administrador institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    foreach (['participation', 'completion', 'performance', 'adoption'] as $report) {
        $this->getJson('/api/v1/academic/reports/organizations/'.$report)->assertForbidden();
    }
});

it('rechaza consultar los indicadores institucionales sin el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/reports/organizations/participation')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/organizations/completion')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/organizations/performance')->assertForbidden();
    $this->getJson('/api/v1/academic/reports/organizations/adoption')->assertForbidden();
});

it('requiere autenticacion para consultar los indicadores institucionales', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/reports/organizations/participation')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/organizations/completion')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/organizations/performance')->assertUnauthorized();
    $this->getJson('/api/v1/academic/reports/organizations/adoption')->assertUnauthorized();
});

// Exercise the controller's scope contract separately from the current role matrix.
function actingAsReportUserWithResolvedScope(string $organizationId): void
{
    $user = actingAsRole(Role::SuperAdmin);
    $resolver = Mockery::mock(AccessibleOrganizationsResolver::class);
    $resolver->shouldReceive('resolveForPermission')
        ->once()->with((string) $user->getAuthIdentifier(), Permission::ViewReports)
        ->andReturn([$organizationId]);
    app()->instance(AccessibleOrganizationsResolver::class, $resolver);
}

it('limita los reportes al alcance resuelto sin filtro explicito', function (): void {
    /** @var TestCase $this */
    $own = persistedReportOrganization();
    persistedReportOrganization();
    actingAsReportUserWithResolvedScope($own->id()->value());

    $response = $this->getJson('/api/v1/academic/reports/organizations/participation')->assertOk();

    expect(collect($response->json('data'))->pluck('organization_id')->all())->toBe([$own->id()->value()]);
});

it('rechaza pedir explicitamente una organizacion ajena', function (): void {
    /** @var TestCase $this */
    $own = persistedReportOrganization();
    $other = persistedReportOrganization();
    actingAsReportUserWithResolvedScope($own->id()->value());

    $this->getJson('/api/v1/academic/reports/organizations/participation?organization_ids[]='.$other->id()->value())
        ->assertStatus(403)
        ->assertJsonPath('code', 'ORGANIZATION_NOT_ACCESSIBLE');
});

it('permite filtrar explicitamente por una organizacion dentro del alcance resuelto', function (): void {
    /** @var TestCase $this */
    $own = persistedReportOrganization();
    actingAsReportUserWithResolvedScope($own->id()->value());

    $this->getJson('/api/v1/academic/reports/organizations/participation?organization_ids[]='.$own->id()->value())
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
