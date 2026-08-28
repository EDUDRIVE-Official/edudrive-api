<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('el endpoint de politicas vigentes no requiere autenticacion', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/legal/policies')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('lista las politicas vigentes con su version actual', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $this->postJson('/api/v1/legal/policies', ['key' => 'privacy_policy'])->assertCreated();

    $this->getJson('/api/v1/legal/policies')
        ->assertOk()
        ->assertJsonPath('data.0.key', 'privacy_policy')
        ->assertJsonPath('data.0.version', 1);
});

it('publica una nueva version con el permiso legal_policies.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/legal/policies', ['key' => 'terms_of_service'])
        ->assertCreated()
        ->assertJsonPath('data.version', 1);

    $this->postJson('/api/v1/legal/policies', ['key' => 'terms_of_service'])
        ->assertCreated()
        ->assertJsonPath('data.version', 2);
});

it('rechaza publicar una politica sin el permiso legal_policies.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/legal/policies', ['key' => 'privacy_policy'])
        ->assertForbidden();
});

it('registra el consentimiento del usuario autenticado a una politica vigente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $this->postJson('/api/v1/legal/policies', ['key' => 'privacy_policy'])->assertCreated();

    actingAsRole(Role::Student);

    $this->postJson('/api/v1/legal/consents', ['policy_key' => 'privacy_policy'])
        ->assertCreated()
        ->assertJsonPath('data.policy_key', 'privacy_policy')
        ->assertJsonPath('data.policy_version', 1);

    $this->getJson('/api/v1/legal/me/consents')
        ->assertOk()
        ->assertJsonPath('data.0.policy_key', 'privacy_policy');
});

it('rechaza registrar consentimiento a una politica inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->postJson('/api/v1/legal/consents', ['policy_key' => 'inexistente'])
        ->assertStatus(404)
        ->assertJsonPath('code', 'POLICY_NOT_FOUND');
});

it('requiere autenticacion para registrar consentimiento o consultar el historial propio', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/legal/consents', ['policy_key' => 'privacy_policy'])->assertUnauthorized();
    $this->getJson('/api/v1/legal/me/consents')->assertUnauthorized();
});
