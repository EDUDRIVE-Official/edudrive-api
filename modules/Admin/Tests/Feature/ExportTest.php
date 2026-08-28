<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('exporta los registros de auditoria a csv con el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/admin/operations/audit-logs/export')
        ->assertOk()
        ->assertJsonPath('data.format', 'csv')
        ->assertJsonStructure(['data' => ['url', 'expires_at', 'row_count', 'format']]);
});

it('permite exportar auditoria al administrador institucional', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/admin/operations/audit-logs/export')
        ->assertOk();
});

it('rechaza exportar auditoria sin el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/admin/operations/audit-logs/export')
        ->assertForbidden();
});

it('requiere autenticacion para exportar auditoria', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/admin/operations/audit-logs/export')
        ->assertUnauthorized();
});
