<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

uses(RefreshDatabase::class);

function persistedSimulationSessionUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de simulacion',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedSimulationSessionSimulatorId(): string
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    return $simulator->id()->value();
}

function newPersistableSimulationSession(?string $userId = null, ?string $simulatorId = null): SimulationSession
{
    return SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId ?? persistedSimulationSessionUserId(),
        simulatorId: $simulatorId ?? persistedSimulationSessionSimulatorId(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
}

it('guarda y recupera una sesion de simulacion por identificador', function (): void {
    $session = newPersistableSimulationSession();

    app(SimulationSessionRepository::class)->save($session);
    $found = app(SimulationSessionRepository::class)->findById($session->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($session->id()))->toBeTrue()
        ->and($found?->userId())->toBe($session->userId())
        ->and($found?->simulatorId())->toBe($session->simulatorId())
        ->and($found?->vehicleType())->toBe('sedan')
        ->and($found?->scenario())->toBe('circuito-urbano')
        ->and($found?->plannedDurationMinutes())->toBe(45)
        ->and($found?->status())->toBe(SimulationSessionStatus::Scheduled)
        ->and($found?->history())->toBe([]);
});

it('guarda y recupera las fechas reales y el historial', function (): void {
    $session = newPersistableSimulationSession();
    $session->start(new DateTimeImmutable('2026-09-01T10:05:00+00:00'));
    $session->complete(new DateTimeImmutable('2026-09-01T10:50:00+00:00'));
    app(SimulationSessionRepository::class)->save($session);

    $found = app(SimulationSessionRepository::class)->findById($session->id());

    expect($found?->status())->toBe(SimulationSessionStatus::Completed)
        ->and($found?->startedAt())->not->toBeNull()
        ->and($found?->endedAt())->not->toBeNull()
        ->and($found?->actualDurationMinutes())->toBe(45)
        ->and($found?->history())->toHaveCount(2);
});

it('lista todas las sesiones de un usuario', function (): void {
    $repository = app(SimulationSessionRepository::class);
    $userId = persistedSimulationSessionUserId();

    $repository->save(newPersistableSimulationSession($userId));
    $repository->save(newPersistableSimulationSession($userId));
    $repository->save(newPersistableSimulationSession());

    expect($repository->allForUser($userId))->toHaveCount(2);
});

it('lista todas las sesiones registradas', function (): void {
    $repository = app(SimulationSessionRepository::class);
    $repository->save(newPersistableSimulationSession());
    $repository->save(newPersistableSimulationSession());

    expect($repository->all())->toHaveCount(2);
});

it('reemplaza el historial en vez de duplicarlo al guardar de nuevo', function (): void {
    $repository = app(SimulationSessionRepository::class);
    $session = newPersistableSimulationSession();
    $session->start(new DateTimeImmutable('now'));
    $repository->save($session);
    $repository->save($session);

    $found = $repository->findById($session->id());

    expect($found?->history())->toHaveCount(1);
});
