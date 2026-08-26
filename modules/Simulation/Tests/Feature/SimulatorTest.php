<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedSimulatorFeature(?string $deviceIdentifier = null): Simulator
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString($deviceIdentifier ?? 'SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    return $simulator;
}

it('registra un simulador con el permiso simulators.manage y devuelve la llave en texto plano', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/simulation/simulators', [
        'device_identifier' => 'SIM-00042',
        'software_version' => '2.3.1',
        'location' => 'Sede Cartago',
    ])
        ->assertCreated()
        ->assertJsonPath('data.device_identifier', 'SIM-00042')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.location', 'Sede Cartago')
        ->assertJson(fn ($json) => $json->where('data.integration_key', fn ($value) => is_string($value) && strlen($value) === 64)->etc());
});

it('rechaza registrar un simulador sin el permiso simulators.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/simulation/simulators', [
        'device_identifier' => 'SIM-00042',
        'software_version' => '1.0.0',
    ])->assertForbidden();
});

it('rechaza registrar un segundo simulador con el mismo identificador de dispositivo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $simulator = persistedSimulatorFeature();

    $this->postJson('/api/v1/simulation/simulators', [
        'device_identifier' => $simulator->deviceIdentifier()->value(),
        'software_version' => '1.0.0',
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'SIMULATOR_ALREADY_EXISTS');
});

it('lista los simuladores con el permiso simulators.view', function (): void {
    /** @var TestCase $this */
    $simulator = persistedSimulatorFeature();
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/simulation/simulators')
        ->assertOk()
        ->assertJsonPath('data.0.id', $simulator->id()->value())
        ->assertJsonMissingPath('data.0.integration_key');
});

it('consulta un simulador por id sin exponer la llave de integracion', function (): void {
    /** @var TestCase $this */
    $simulator = persistedSimulatorFeature();
    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/simulation/simulators/{$simulator->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $simulator->id()->value())
        ->assertJsonMissingPath('data.integration_key');
});

it('rechaza listar y consultar simuladores sin el permiso simulators.view', function (): void {
    /** @var TestCase $this */
    $simulator = persistedSimulatorFeature();
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/simulation/simulators')->assertForbidden();
    $this->getJson("/api/v1/simulation/simulators/{$simulator->id()->value()}")->assertForbidden();
});

it('suspende, reactiva y retira un simulador con el permiso simulators.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $simulator = persistedSimulatorFeature();
    $id = $simulator->id()->value();

    $this->postJson("/api/v1/simulation/simulators/{$id}/suspend", ['reason' => 'Mantenimiento'])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended');

    $this->postJson("/api/v1/simulation/simulators/{$id}/reactivate")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->postJson("/api/v1/simulation/simulators/{$id}/retire", ['reason' => 'Fin de vida util'])
        ->assertOk()
        ->assertJsonPath('data.status', 'retired');
});

it('rechaza mutar un simulador sin el permiso simulators.manage', function (): void {
    /** @var TestCase $this */
    $simulator = persistedSimulatorFeature();
    actingAsRole(Role::Teacher);
    $id = $simulator->id()->value();

    $this->postJson("/api/v1/simulation/simulators/{$id}/suspend")->assertForbidden();
    $this->postJson("/api/v1/simulation/simulators/{$id}/reactivate")->assertForbidden();
    $this->postJson("/api/v1/simulation/simulators/{$id}/retire")->assertForbidden();
    $this->postJson("/api/v1/simulation/simulators/{$id}/rotate-key")->assertForbidden();
});

it('responde 422 ante una transicion invalida', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $simulator = persistedSimulatorFeature();

    $this->postJson("/api/v1/simulation/simulators/{$simulator->id()->value()}/reactivate")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_SIMULATOR_TRANSITION');
});

it('rota la llave de integracion y devuelve el nuevo valor en texto plano', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $simulator = persistedSimulatorFeature();

    $this->postJson("/api/v1/simulation/simulators/{$simulator->id()->value()}/rotate-key")
        ->assertOk()
        ->assertJson(fn ($json) => $json->where('data.integration_key', fn ($value) => is_string($value) && strlen($value) === 64)->etc());
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $simulator = persistedSimulatorFeature();

    $this->getJson('/api/v1/simulation/simulators')->assertUnauthorized();
    $this->getJson("/api/v1/simulation/simulators/{$simulator->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/simulation/simulators', ['device_identifier' => 'SIM-1', 'software_version' => '1.0.0'])->assertUnauthorized();
});
