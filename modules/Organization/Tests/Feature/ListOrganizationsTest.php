<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

use function Pest\Laravel\getJson;

use Tests\TestCase;

it('lista las organizaciones existentes', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();

    $organizations = app(OrganizationRepository::class);

    $organizations->save(Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Centro Educativo EDUDRIVE'),
        type: OrganizationType::EducationalCenter,
    ));

    getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Centro Educativo EDUDRIVE')
        ->assertJsonPath('data.0.campuses', []);
});

it('incluye las sedes de una organización en la respuesta', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();

    $organizations = app(OrganizationRepository::class);

    $campusId = (string) Str::uuid();

    $organization = Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    $organization->addCampus(
        Campus::create(id: $campusId, name: 'Sede Cabo Velas'),
    );

    $organizations->save($organization);

    getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.campuses', [
            ['id' => $campusId, 'name' => 'Sede Cabo Velas'],
        ]);
});

it('rechaza la consulta sin autenticación', function (): void {
    getJson('/api/v1/organizations')->assertUnauthorized();
});
