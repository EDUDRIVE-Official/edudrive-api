<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('consulta el resumen del sistema con el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/admin/reports/summary')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_users',
                'total_enrollments',
                'total_achievements_granted',
                'total_certificates_issued',
                'total_simulation_sessions',
            ],
        ]);
});

it('otorga reports.view tambien al administrador institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/admin/reports/summary')->assertOk();
});

it('rechaza consultar el resumen sin el permiso reports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/admin/reports/summary')->assertForbidden();
});

it('requiere autenticacion para consultar el resumen del sistema', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/admin/reports/summary')->assertUnauthorized();
});
