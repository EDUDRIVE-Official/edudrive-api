<?php

declare(strict_types=1);

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

it('crea una organización sin sedes', function (): void {
    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    expect($organization->name()->value())->toBe('Escuela de Manejo EDUDRIVE')
        ->and($organization->type())->toBe(OrganizationType::DrivingSchool)
        ->and($organization->campuses())->toBe([]);
});

it('permite agregar sedes a una organización', function (): void {
    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    $campus = Campus::create(
        id: '01981a64-8300-7b1d-b442-764ea7f915c1',
        name: 'Sede Cabo Velas',
    );

    $organization->addCampus($campus);

    expect($organization->campuses())->toHaveCount(1)
        ->and($organization->campuses()[0]->name())->toBe('Sede Cabo Velas');
});
