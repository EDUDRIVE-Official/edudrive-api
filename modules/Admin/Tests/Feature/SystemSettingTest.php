<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('establece una configuracion con el permiso system_settings.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'true'])
        ->assertOk()
        ->assertJsonPath('data.key', 'maintenance_mode')
        ->assertJsonPath('data.value', 'true');
});

it('audita el cambio de una configuracion con el valor anterior y el nuevo', function (): void {
    /** @var TestCase $this */
    $actor = actingAsRole(Role::SuperAdmin);

    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'true'])->assertOk();
    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'false'])->assertOk();

    $entry = AuditLogModel::query()->where('action', 'admin.system_setting_changed')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($actor->id)
        ->and($entry->metadata['old_value'])->toBe('true')
        ->and($entry->metadata['new_value'])->toBe('false');
});

it('rechaza establecer una configuracion sin el permiso system_settings.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'true'])
        ->assertForbidden();
});

it('lista las configuraciones registradas con el permiso system_settings.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'true'])->assertOk();

    $this->getJson('/api/v1/admin/settings')
        ->assertOk()
        ->assertJsonPath('data.0.key', 'maintenance_mode');
});

it('rechaza listar configuraciones sin el permiso system_settings.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/admin/settings')->assertForbidden();
});

it('consulta una configuracion por clave', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'true'])->assertOk();

    $this->getJson('/api/v1/admin/settings/maintenance_mode')
        ->assertOk()
        ->assertJsonPath('data.value', 'true');
});

it('rechaza consultar una configuracion inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/admin/settings/inexistente')
        ->assertStatus(404)
        ->assertJsonPath('code', 'SYSTEM_SETTING_NOT_FOUND');
});

it('requiere autenticacion para todos los endpoints de configuracion', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/admin/settings')->assertUnauthorized();
    $this->getJson('/api/v1/admin/settings/maintenance_mode')->assertUnauthorized();
    $this->putJson('/api/v1/admin/settings/maintenance_mode', ['value' => 'true'])->assertUnauthorized();
});
