<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Application\Commands\CancelSimulationSessionCommand;
use Modules\Simulation\Application\Commands\CompleteSimulationSessionCommand;
use Modules\Simulation\Application\Commands\ScheduleSimulationSessionCommand;
use Modules\Simulation\Application\Commands\StartSimulationSessionCommand;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Exceptions\SimulatorNotAvailable;
use Modules\Simulation\Application\Exceptions\SimulatorNotFound;
use Modules\Simulation\Application\Queries\GetMySimulationSessionsQuery;
use Modules\Simulation\Application\Queries\GetSimulationSessionQuery;
use Modules\Simulation\Application\Queries\ListSimulationSessionsQuery;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Application\UseCases\CancelSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\CompleteSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\GetMySimulationSessionsHandler;
use Modules\Simulation\Application\UseCases\GetSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\ListSimulationSessionsHandler;
use Modules\Simulation\Application\UseCases\ScheduleSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\StartSimulationSessionHandler;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Exceptions\InvalidSimulationSessionTransition;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final class InMemorySimulationSessionRepository implements SimulationSessionRepository
{
    /** @var array<string, SimulationSession> */
    public array $items = [];

    public function save(SimulationSession $session): void
    {
        $this->items[$session->id()->value()] = $session;
    }

    public function findById(SimulationSessionId $id): ?SimulationSession
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<SimulationSession> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (SimulationSession $session): bool => $session->userId() === $userId,
        ));
    }

    /** @return list<SimulationSession> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

final class InMemorySimulatorRepositoryForSessions implements SimulatorRepository
{
    /** @var array<string, Simulator> */
    public array $items = [];

    public function save(Simulator $simulator): void
    {
        $this->items[$simulator->id()->value()] = $simulator;
    }

    public function findById(SimulatorId $id): ?Simulator
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByDeviceIdentifier(DeviceIdentifier $deviceIdentifier): ?Simulator
    {
        throw new LogicException('No usado en esta prueba.');
    }

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?Simulator
    {
        throw new LogicException('No usado en esta prueba.');
    }

    /** @return list<Simulator> */
    public function all(): array
    {
        throw new LogicException('No usado en esta prueba.');
    }
}

function persistedActiveSimulatorFor(InMemorySimulatorRepositoryForSessions $repository): Simulator
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    $repository->save($simulator);

    return $simulator;
}

function persistedSimulationSessionFor(
    InMemorySimulationSessionRepository $repository,
    string $simulatorId,
    ?string $userId = null,
): SimulationSession {
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId ?? (string) Str::uuid(),
        simulatorId: $simulatorId,
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
    $repository->save($session);

    return $session;
}

it('programa una sesion nueva para un simulador activo', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $userId = (string) Str::uuid();

    $response = (new ScheduleSimulationSessionHandler($sessions, $simulators))->handle(new ScheduleSimulationSessionCommand(
        userId: $userId,
        simulatorId: $simulator->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    ));

    expect($response)->toBeInstanceOf(SimulationSessionResponse::class)
        ->and($response->userId)->toBe($userId)
        ->and($response->simulatorId)->toBe($simulator->id()->value())
        ->and($response->status)->toBe('scheduled')
        ->and($response->plannedDurationMinutes)->toBe(45);
});

it('rechaza programar una sesion para un simulador inexistente', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;

    expect(fn () => (new ScheduleSimulationSessionHandler($sessions, $simulators))->handle(new ScheduleSimulationSessionCommand(
        userId: (string) Str::uuid(),
        simulatorId: (string) Str::uuid(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('now'),
        plannedDurationMinutes: 45,
    )))->toThrow(SimulatorNotFound::class);
});

it('rechaza programar una sesion para un simulador que no esta activo', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $simulator->suspend(null, new DateTimeImmutable('now'));
    $simulators->save($simulator);

    expect(fn () => (new ScheduleSimulationSessionHandler($sessions, $simulators))->handle(new ScheduleSimulationSessionCommand(
        userId: (string) Str::uuid(),
        simulatorId: $simulator->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('now'),
        plannedDurationMinutes: 45,
    )))->toThrow(SimulatorNotAvailable::class);
});

it('el dueno inicia, completa su propia sesion', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $userId = (string) Str::uuid();
    $session = persistedSimulationSessionFor($sessions, $simulator->id()->value(), $userId);

    $started = (new StartSimulationSessionHandler($sessions))->handle(new StartSimulationSessionCommand($session->id()->value(), $userId, false));
    expect($started->status)->toBe('in_progress');

    $completed = (new CompleteSimulationSessionHandler($sessions))->handle(new CompleteSimulationSessionCommand($session->id()->value(), $userId, false));
    expect($completed->status)->toBe('completed');
});

it('un tercero con permiso ampliado puede cancelar una sesion ajena', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $session = persistedSimulationSessionFor($sessions, $simulator->id()->value());

    $response = (new CancelSimulationSessionHandler($sessions))->handle(new CancelSimulationSessionCommand(
        $session->id()->value(),
        (string) Str::uuid(),
        true,
        'Simulador requerido para otra actividad',
    ));

    expect($response->status)->toBe('cancelled');
});

it('rechaza mutar una sesion ajena sin permiso ampliado', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $session = persistedSimulationSessionFor($sessions, $simulator->id()->value());
    $otherUserId = (string) Str::uuid();

    expect(fn () => (new StartSimulationSessionHandler($sessions))->handle(new StartSimulationSessionCommand($session->id()->value(), $otherUserId, false)))
        ->toThrow(SimulationSessionNotFound::class);
    expect(fn () => (new CancelSimulationSessionHandler($sessions))->handle(new CancelSimulationSessionCommand($session->id()->value(), $otherUserId, false)))
        ->toThrow(SimulationSessionNotFound::class);
});

it('rechaza mutar una sesion inexistente', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $id = (string) Str::uuid();
    $userId = (string) Str::uuid();

    expect(fn () => (new StartSimulationSessionHandler($sessions))->handle(new StartSimulationSessionCommand($id, $userId, true)))
        ->toThrow(SimulationSessionNotFound::class);
});

it('propaga el rechazo de dominio ante una transicion invalida', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $userId = (string) Str::uuid();
    $session = persistedSimulationSessionFor($sessions, $simulator->id()->value(), $userId);

    expect(fn () => (new CompleteSimulationSessionHandler($sessions))->handle(new CompleteSimulationSessionCommand($session->id()->value(), $userId, false)))
        ->toThrow(InvalidSimulationSessionTransition::class);
});

it('devuelve la sesion al dueno o a un tercero con permiso ampliado', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $userId = (string) Str::uuid();
    $session = persistedSimulationSessionFor($sessions, $simulator->id()->value(), $userId);

    $own = (new GetSimulationSessionHandler($sessions))->handle(new GetSimulationSessionQuery($session->id()->value(), $userId, false));
    expect($own->id)->toBe($session->id()->value());

    $others = (new GetSimulationSessionHandler($sessions))->handle(new GetSimulationSessionQuery($session->id()->value(), (string) Str::uuid(), true));
    expect($others->id)->toBe($session->id()->value());
});

it('rechaza consultar la sesion de un tercero sin permiso ampliado', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $session = persistedSimulationSessionFor($sessions, $simulator->id()->value());

    expect(fn () => (new GetSimulationSessionHandler($sessions))->handle(new GetSimulationSessionQuery($session->id()->value(), (string) Str::uuid(), false)))
        ->toThrow(SimulationSessionNotFound::class);
});

it('lista las sesiones del usuario autenticado', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    $userId = (string) Str::uuid();
    persistedSimulationSessionFor($sessions, $simulator->id()->value(), $userId);
    persistedSimulationSessionFor($sessions, $simulator->id()->value(), $userId);
    persistedSimulationSessionFor($sessions, $simulator->id()->value());

    $responses = (new GetMySimulationSessionsHandler($sessions))->handle(new GetMySimulationSessionsQuery($userId));

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(SimulationSessionResponse::class);
});

it('lista todas las sesiones registradas', function (): void {
    $sessions = new InMemorySimulationSessionRepository;
    $simulators = new InMemorySimulatorRepositoryForSessions;
    $simulator = persistedActiveSimulatorFor($simulators);
    persistedSimulationSessionFor($sessions, $simulator->id()->value());
    persistedSimulationSessionFor($sessions, $simulator->id()->value());

    $responses = (new ListSimulationSessionsHandler($sessions))->handle(new ListSimulationSessionsQuery);

    expect($responses)->toHaveCount(2);
});
