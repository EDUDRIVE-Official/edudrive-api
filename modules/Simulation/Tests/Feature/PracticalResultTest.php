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
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedPracticalResultUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de resultado practico',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedPracticalResultSession(?string $userId = null, bool $completed = true): SimulationSession
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId ?? persistedPracticalResultUserId(),
        simulatorId: $simulator->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );

    if ($completed) {
        $session->start(new DateTimeImmutable('2026-09-01T10:00:00+00:00'));
        $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    }

    app(SimulationSessionRepository::class)->save($session);

    return $session;
}

it('el dueno consulta el resultado practico de su propia sesion completada', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedPracticalResultSession($userId);
    app(TelemetryEventRepository::class)->saveBatch([
        TelemetryEvent::record((string) Str::uuid(), $session->id()->value(), TelemetryEventType::Infraction, 'Exceso de velocidad', new DateTimeImmutable('2026-09-01T10:12:00+00:00')),
    ]);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/result")
        ->assertOk()
        ->assertJsonPath('data.session_id', $session->id()->value())
        ->assertJsonPath('data.outcome', 'passed')
        ->assertJsonPath('data.score', 90)
        ->assertJsonCount(1, 'data.errors')
        ->assertJsonCount(1, 'data.recommendations');
});

it('responde 422 si la sesion no ha finalizado', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $session = persistedPracticalResultSession($userId, completed: false);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/result")
        ->assertStatus(422)
        ->assertJsonPath('code', 'PRACTICAL_RESULT_NOT_AVAILABLE');
});

it('rechaza consultar el resultado de una sesion ajena sin simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    $session = persistedPracticalResultSession();
    actingAsRole(Role::Student);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/result")
        ->assertNotFound()
        ->assertJsonPath('code', 'SIMULATION_SESSION_NOT_FOUND');
});

it('permite consultar el resultado de una sesion ajena con simulation_sessions.view', function (): void {
    /** @var TestCase $this */
    $session = persistedPracticalResultSession();
    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/result")
        ->assertOk()
        ->assertJsonPath('data.session_id', $session->id()->value());
});

it('requiere autenticacion', function (): void {
    /** @var TestCase $this */
    $session = persistedPracticalResultSession();

    $this->getJson("/api/v1/simulation/sessions/{$session->id()->value()}/result")
        ->assertUnauthorized();
});
