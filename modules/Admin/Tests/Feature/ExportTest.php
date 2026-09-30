<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

beforeEach(function (): void {
    Storage::fake('s3')
        ->buildTemporaryUrlsUsing(fn (string $path, DateTimeInterface $expiration): string => 'https://storage.example.invalid/'.rawurlencode($path).'?expires='.$expiration->getTimestamp());
});

uses(RefreshDatabase::class);

it('exporta los registros de auditoria a csv de forma asincrona con el permiso exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $response = $this->postJson('/api/v1/admin/operations/audit-logs/export')
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.type', 'export.audit_logs');

    $asyncJobId = $response->json('data.id');

    $this->getJson("/api/v1/async-jobs/{$asyncJobId}")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.result.format', 'csv');
});

it('rechaza exportar auditoria al administrador institucional sin exports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/admin/operations/audit-logs/export')
        ->assertForbidden();
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
