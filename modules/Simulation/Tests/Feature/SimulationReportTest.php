<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

it('consulta los cuatro reportes de simulacion con el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/simulation/reports/sessions')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/simulation/reports/telemetry')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/simulation/reports/evolution')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/v1/simulation/reports/risks')->assertOk()->assertJsonStructure(['data']);
});

it('permite consultar los reportes de simulacion al administrador institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/simulation/reports/sessions')->assertOk();
});

it('rechaza consultar los reportes de simulacion sin el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/simulation/reports/sessions')->assertForbidden();
    $this->getJson('/api/v1/simulation/reports/telemetry')->assertForbidden();
    $this->getJson('/api/v1/simulation/reports/evolution')->assertForbidden();
    $this->getJson('/api/v1/simulation/reports/risks')->assertForbidden();
});

it('requiere autenticacion para consultar los reportes de simulacion', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/simulation/reports/sessions')->assertUnauthorized();
    $this->getJson('/api/v1/simulation/reports/telemetry')->assertUnauthorized();
    $this->getJson('/api/v1/simulation/reports/evolution')->assertUnauthorized();
    $this->getJson('/api/v1/simulation/reports/risks')->assertUnauthorized();
});
