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

function persistedTelemetryFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de telemetria feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

/** @return array{0: Simulator, 1: string} */
function persistedTelemetryFeatureSimulator(): array
{
    $integrationKey = IntegrationKey::generate();
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: $integrationKey,
    );
    app(SimulatorRepository::class)->save($simulator);

    return [$simulator, (string) $integrationKey->plainValue()];
}

function persistedTelemetryFeatureSession(string $userId, string $simulatorId, bool $inProgress = true): SimulationSession
{
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId,
        simulatorId: $simulatorId,
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );

    if ($inProgress) {
        $session->start(new DateTimeImmutable('2026-09-01T10:00:00+00:00'));
    }

    app(SimulationSessionRepository::class)->save($session);

    return $session;
}

it('acepta un lote de telemetria autenticado con la llave del simulador', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry", [
            'samples' => [
                ['speed_kph' => 40.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 1.1, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T10:10:00+00:00'],
            ],
            'events' => [
                ['type' => 'collision', 'details' => 'Colision leve', 'occurred_at' => '2026-09-01T10:11:00+00:00'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.samples_recorded', 1)
        ->assertJsonPath('data.events_recorded', 1);
});

it('rechaza un lote de telemetria sin llave de simulador', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());

    $this->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry", ['samples' => [], 'events' => []])
        ->assertUnauthorized();
});

it('rechaza un lote de telemetria con una llave que no corresponde a ningun simulador', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());

    $this->withHeaders(['Authorization' => 'Bearer llave-invalida'])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry", ['samples' => [], 'events' => []])
        ->assertUnauthorized();
});

it('rechaza telemetria de un simulador distinto al de la sesion', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedTelemetryFeatureSimulator();
    [, $otherPlainKey] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());

    $this->withHeaders(['Authorization' => "Bearer {$otherPlainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry", ['samples' => [], 'events' => []])
        ->assertNotFound()
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_FOUND');
});

it('rechaza telemetria para una sesion que no esta en curso', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value(), inProgress: false);

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry", ['samples' => [], 'events' => []])
        ->assertStatus(422)
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_IN_PROGRESS');
});

it('el dueno consulta la telemetria de su propia sesion', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());
    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry", [
            'samples' => [['speed_kph' => 30.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 0.0, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T10:10:00+00:00']],
            'events' => [],
        ])->assertCreated();

    actingAsUserId($userId);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry")
        ->assertOk()
        ->assertJsonPath('data.session_id', $session->id()->value())
        ->assertJsonCount(1, 'data.samples');
});

it('rechaza consultar la telemetria de una sesion ajena sin simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());

    actingAsRole(Role::Student);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry")
        ->assertNotFound()
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_FOUND');
});

it('permite consultar la telemetria de una sesion ajena con simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedTelemetryFeatureSimulator();
    $userId = persistedTelemetryFeatureUserId();
    $session = persistedTelemetryFeatureSession($userId, $simulator->id()->value());

    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/telemetry")
        ->assertOk()
        ->assertJsonPath('data.session_id', $session->id()->value());
});
