<?php

declare(strict_types=1);

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;
use Tests\TestCase;

it('guarda y recupera una organización con sus sedes', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(OrganizationRepository::class);

    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    $organization->addCampus(
        Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c1', name: 'Sede Cabo Velas'),
    );

    $repository->save($organization);

    $persisted = $repository->findById($organization->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->name()->value())->toBe('Escuela de Manejo EDUDRIVE')
        ->and($persisted?->campuses())->toHaveCount(1)
        ->and($persisted?->campuses()[0]->name())->toBe('Sede Cabo Velas');
});

it('conserva y agrega sedes al guardar la misma organización de nuevo', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(OrganizationRepository::class);

    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c4'),
        name: OrganizationName::fromString('Escuela de Manejo Tempisque'),
        type: OrganizationType::DrivingSchool,
    );

    $organization->addCampus(
        Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c5', name: 'Sede Cabo Velas'),
    );

    $repository->save($organization);

    $organization->addCampus(
        Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c6', name: 'Sede Tempisque'),
    );

    $repository->save($organization);

    $persisted = $repository->findById($organization->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->campuses())->toHaveCount(2);

    $campusNames = array_map(
        static fn (Campus $campus): string => $campus->name(),
        $persisted?->campuses() ?? [],
    );

    expect($campusNames)->toContain('Sede Cabo Velas')
        ->and($campusNames)->toContain('Sede Tempisque');
});

it('lista todas las organizaciones', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(OrganizationRepository::class);

    $repository->save(Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c2'),
        name: OrganizationName::fromString('Centro Educativo A'),
        type: OrganizationType::EducationalCenter,
    ));

    $repository->save(Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c3'),
        name: OrganizationName::fromString('Centro Educativo B'),
        type: OrganizationType::EducationalCenter,
    ));

    expect($repository->all())->toHaveCount(2);
});
