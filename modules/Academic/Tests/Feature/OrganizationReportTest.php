<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

it('consulta los cuatro indicadores institucionales con el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/academic/reports/organizations/participation')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/organizations/completion')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/organizations/performance')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/academic/reports/organizations/adoption')->assertOk()->assertJsonStructure(['data']);
});

it('permite consultar los indicadores institucionales al administrador institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/academic/reports/organizations/participation')->assertOk();
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
