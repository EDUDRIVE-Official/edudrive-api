<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('consulta la salud del sistema con el permiso system_operations.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/admin/operations/health')
        ->assertOk()
        ->assertJsonPath('data.status', 'healthy')
        ->assertJsonPath('data.database', 'up');
});

it('rechaza consultar la salud del sistema sin el permiso system_operations.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/admin/operations/health')->assertForbidden();
});

it('lista los registros de auditoria con el permiso system_operations.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/admin/operations/audit-logs')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('rechaza listar auditoria sin el permiso system_operations.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/admin/operations/audit-logs')->assertForbidden();
});

it('requiere autenticacion para los endpoints de operacion del sistema', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/admin/operations/health')->assertUnauthorized();
    $this->getJson('/api/v1/admin/operations/audit-logs')->assertUnauthorized();
});
