<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

it('agrega una sede a una organización existente', function (): void {
    $organizations = app(OrganizationRepository::class);

    $organizationId = OrganizationId::fromString((string) Str::uuid());

    $organizations->save(Organization::create(
        id: $organizationId,
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    ));

    actingAsAuthenticatedUser();

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
    actingAsAuthenticatedUser();

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

    actingAsAuthenticatedUser();

    postJson("/api/v1/organizations/{$organizationId->value()}/campuses", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/organizations/'.((string) Str::uuid()).'/campuses', [
        'name' => 'Sede Sin Autenticación',
    ])->assertUnauthorized();
});
