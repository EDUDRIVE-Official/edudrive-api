<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedSimulationSessionFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de simulacion feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedActiveSimulatorFeature(): Simulator
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    return $simulator;
}

function persistedSimulationSessionFeature(?string $userId = null, ?string $simulatorId = null): SimulationSession
{
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId ?? persistedSimulationSessionFeatureUserId(),
        simulatorId: $simulatorId ?? persistedActiveSimulatorFeature()->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
    app(SimulationSessionRepository::class)->save($session);

    return $session;
}

it('programa una sesion propia sin necesitar ningun permiso especial', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $simulator = persistedActiveSimulatorFeature();

    $this->postJson('/api/v1/simulation/sessions', [
        'simulator_id' => $simulator->id()->value(),
        'vehicle_type' => 'sedan',
        'scenario' => 'circuito-urbano',
        'scheduled_at' => '2026-09-01T10:00:00+00:00',
        'planned_duration_minutes' => 45,
    ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.simulator_id', $simulator->id()->value())
        ->assertJsonPath('data.status', 'scheduled');
});

it('rechaza programar una sesion para un simulador que no esta activo', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());
    $simulator = persistedActiveSimulatorFeature();
    $simulator->suspend(null, new DateTimeImmutable('now'));
    app(SimulatorRepository::class)->save($simulator);

    $this->postJson('/api/v1/simulation/sessions', [
        'simulator_id' => $simulator->id()->value(),
        'vehicle_type' => 'sedan',
        'scenario' => 'circuito-urbano',
        'scheduled_at' => '2026-09-01T10:00:00+00:00',
        'planned_duration_minutes' => 45,
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'SIMULATOR_NOT_AVAILABLE');
});

it('lista las sesiones propias del usuario autenticado', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedSimulationSessionFeature($userId);

    $this->getJson('/api/v1/simulation/sessions/me')
        ->assertOk()
        ->assertJsonPath('data.0.id', $session->id()->value());
});

it('consulta una sesion propia por id', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedSimulationSessionFeature($userId);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $session->id()->value());
});

it('rechaza consultar una sesion ajena sin simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $session = persistedSimulationSessionFeature();

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}")
        ->assertNotFound()
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_FOUND');
});

it('permite consultar una sesion ajena con simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $session = persistedSimulationSessionFeature();

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $session->id()->value());
});

it('el dueno inicia, completa su propia sesion', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedSimulationSessionFeature($userId);
    $id = $session->id()->value();

    $this->postJson("/api/v1/simulation/sessions/{$id}/start")
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    $this->postJson("/api/v1/simulation/sessions/{$id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

it('el dueno cancela su propia sesion programada', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedSimulationSessionFeature($userId);

    $this->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/cancel", ['reason' => 'No puedo asistir'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('rechaza mutar una sesion ajena sin simulation_sessions.manage', function (): void {
    /** @var TestCase $this */
    $session = persistedSimulationSessionFeature();
    actingAsRole(Role::Teacher);
    $id = $session->id()->value();

    $this->postJson("/api/v1/simulation/sessions/{$id}/start")->assertNotFound();
    $this->postJson("/api/v1/simulation/sessions/{$id}/cancel")->assertNotFound();
});

it('permite a un administrador cancelar una sesion ajena con simulation_sessions.manage', function (): void {
    /** @var TestCase $this */
    $session = persistedSimulationSessionFeature();
    actingAsRole(Role::SuperAdmin);

    $this->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/cancel", ['reason' => 'Mantenimiento del simulador'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('lista todas las sesiones con el permiso simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    $session = persistedSimulationSessionFeature();
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/simulation/sessions')
        ->assertOk()
        ->assertJsonPath('data.0.id', $session->id()->value());
});

it('rechaza listar todas las sesiones sin el permiso simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/simulation/sessions')->assertForbidden();
});

it('responde 422 ante una transicion invalida', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedSimulationSessionFeature($userId);

    $this->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/complete")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_SIMULATION_SESSION_TRANSITION');
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $session = persistedSimulationSessionFeature();

    $this->getJson('/api/v1/simulation/sessions/me')->assertUnauthorized();
    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/simulation/sessions', [])->assertUnauthorized();
});
