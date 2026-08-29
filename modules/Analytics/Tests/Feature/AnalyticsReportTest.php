<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('requiere autenticacion para solicitar un reporte de analitica', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/analytics/reports', ['type' => 'users_summary'])->assertUnauthorized();
});

it('rechaza solicitar un reporte sin el permiso analytics.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/analytics/reports', ['type' => 'users_summary'])->assertForbidden();
});

it('solicita un reporte de analitica y consulta su resultado via el endpoint generico de trabajos asincronos', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $response = $this->postJson('/api/v1/analytics/reports', ['type' => 'users_summary'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.type', 'analytics.users_summary');

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.result.total', 1);
});

it('rechaza un tipo de reporte invalido', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/analytics/reports', ['type' => 'no_existe'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_ANALYTICS_REPORT_TYPE');
});
