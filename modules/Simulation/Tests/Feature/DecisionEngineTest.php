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

function persistedDecisionEngineFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de decisiones feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

/** @return array{0: Simulator, 1: string} */
function persistedDecisionEngineFeatureSimulator(): array
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

function persistedDecisionEngineFeatureSession(string $userId, string $simulatorId, bool $inProgress = true): SimulationSession
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

it('acepta un lote de puntos de decision autenticado con la llave del simulador', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value());

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", [
            'decisions' => [
                ['id' => (string) Str::uuid(), 'road_context' => 'Semáforo en amarillo', 'risk_level' => 'high', 'driver_reaction' => 'braked', 'occurred_at' => '2026-09-01T10:12:00+00:00'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.decisions_recorded', 1);
});

it('ignora un reenvio del mismo lote de decisiones sin duplicar filas', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value());
    $payload = [
        'decisions' => [
            ['id' => (string) Str::uuid(), 'road_context' => 'Semáforo en amarillo', 'risk_level' => 'high', 'driver_reaction' => 'braked', 'occurred_at' => '2026-09-01T10:12:00+00:00'],
        ],
    ];

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", $payload)
        ->assertCreated()
        ->assertJsonPath('data.decisions_recorded', 1);

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", $payload)
        ->assertCreated()
        ->assertJsonPath('data.decisions_recorded', 0);
});

it('acepta un punto de decision que llego tarde para una sesion ya completada', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value());
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    app(SimulationSessionRepository::class)->save($session);

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", [
            'decisions' => [
                ['id' => (string) Str::uuid(), 'road_context' => 'Semáforo en amarillo', 'risk_level' => 'high', 'driver_reaction' => 'braked', 'occurred_at' => '2026-09-01T10:20:00+00:00'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.decisions_recorded', 1);
});

it('rechaza un lote de puntos de decision sin llave de simulador', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value());

    $this->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", ['decisions' => []])
        ->assertUnauthorized();
});

it('rechaza puntos de decision para una sesion que no esta en curso', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value(), inProgress: false);

    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", ['decisions' => []])
        ->assertStatus(422)
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_IN_PROGRESS');
});

it('el dueno consulta el resultado del motor de decisiones de su sesion completada', function (): void {
    /** @var TestCase $this */
    [$simulator, $plainKey] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value());
    $this->withHeaders(['Authorization' => "Bearer {$plainKey}"])
        ->postJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions", [
            'decisions' => [
                ['id' => (string) Str::uuid(), 'road_context' => 'Semáforo en amarillo', 'risk_level' => 'high', 'driver_reaction' => 'braked', 'occurred_at' => '2026-09-01T10:12:00+00:00'],
            ],
        ])->assertCreated();

    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    app(SimulationSessionRepository::class)->save($session);

    actingAsUserId($userId);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions")
        ->assertOk()
        ->assertJsonPath('data.session_id', $session->id()->value())
        ->assertJsonPath('data.appropriate_count', 1)
        ->assertJsonCount(1, 'data.evaluations');
});

it('rechaza consultar el resultado ajeno sin simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    [$simulator] = persistedDecisionEngineFeatureSimulator();
    $userId = persistedDecisionEngineFeatureUserId();
    $session = persistedDecisionEngineFeatureSession($userId, $simulator->id()->value());
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    app(SimulationSessionRepository::class)->save($session);

    actingAsRole(Role::Student);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/decisions")
        ->assertNotFound()
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_FOUND');
});
