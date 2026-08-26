<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Exceptions\InvalidSimulationSessionTransition;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionHistoryEntry;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

function newSimulationSession(?DateTimeImmutable $scheduledAt = null): SimulationSession
{
    return SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        simulatorId: (string) Str::uuid(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: $scheduledAt ?? new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
}

it('se programa en estado scheduled y sin historial', function (): void {
    $session = newSimulationSession();

    expect($session->status())->toBe(SimulationSessionStatus::Scheduled)
        ->and($session->history())->toBe([])
        ->and($session->startedAt())->toBeNull()
        ->and($session->endedAt())->toBeNull()
        ->and($session->actualDurationMinutes())->toBeNull();
});

it('inicia una sesion programada y registra el cambio en el historial', function (): void {
    $session = newSimulationSession();

    $session->start(new DateTimeImmutable('2026-09-01T10:05:00+00:00'));

    expect($session->status())->toBe(SimulationSessionStatus::InProgress)
        ->and($session->startedAt())->not->toBeNull()
        ->and($session->history())->toHaveCount(1);
});

it('rechaza iniciar una sesion que no esta programada', function (): void {
    $session = newSimulationSession();
    $session->start(new DateTimeImmutable('now'));

    expect(fn () => $session->start(new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulationSessionTransition::class);
});

it('completa una sesion en curso y calcula la duracion efectiva', function (): void {
    $session = newSimulationSession();
    $session->start(new DateTimeImmutable('2026-09-01T10:05:00+00:00'));

    $session->complete(new DateTimeImmutable('2026-09-01T10:50:00+00:00'));

    expect($session->status())->toBe(SimulationSessionStatus::Completed)
        ->and($session->endedAt())->not->toBeNull()
        ->and($session->actualDurationMinutes())->toBe(45)
        ->and($session->history())->toHaveCount(2);
});

it('rechaza completar una sesion que no esta en curso', function (): void {
    $session = newSimulationSession();

    expect(fn () => $session->complete(new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulationSessionTransition::class);
});

it('cancela una sesion programada', function (): void {
    $session = newSimulationSession();

    $session->cancel('El usuario no pudo asistir', new DateTimeImmutable('2026-08-31T00:00:00+00:00'));

    expect($session->status())->toBe(SimulationSessionStatus::Cancelled)
        ->and($session->history())->toHaveCount(1)
        ->and($session->history()[0]->reason)->toBe('El usuario no pudo asistir');
});

it('rechaza cancelar una sesion que ya inicio', function (): void {
    $session = newSimulationSession();
    $session->start(new DateTimeImmutable('now'));

    expect(fn () => $session->cancel(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulationSessionTransition::class);
});

it('rechaza cancelar una sesion ya cancelada', function (): void {
    $session = newSimulationSession();
    $session->cancel(null, new DateTimeImmutable('now'));

    expect(fn () => $session->cancel(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulationSessionTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = SimulationSessionId::fromString((string) Str::uuid());
    $userId = (string) Str::uuid();
    $simulatorId = (string) Str::uuid();
    $scheduledAt = new DateTimeImmutable('2026-09-01T10:00:00+00:00');
    $startedAt = new DateTimeImmutable('2026-09-01T10:05:00+00:00');
    $endedAt = new DateTimeImmutable('2026-09-01T10:50:00+00:00');
    $historyEntry = SimulationSessionHistoryEntry::restore(
        SimulationSessionStatus::Scheduled,
        SimulationSessionStatus::InProgress,
        $startedAt,
        null,
    );

    $session = SimulationSession::restore(
        id: $id,
        userId: $userId,
        simulatorId: $simulatorId,
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: $scheduledAt,
        plannedDurationMinutes: 45,
        status: SimulationSessionStatus::Completed,
        startedAt: $startedAt,
        endedAt: $endedAt,
        history: [$historyEntry],
    );

    expect($session->id()->equals($id))->toBeTrue()
        ->and($session->userId())->toBe($userId)
        ->and($session->simulatorId())->toBe($simulatorId)
        ->and($session->vehicleType())->toBe('sedan')
        ->and($session->scenario())->toBe('circuito-urbano')
        ->and($session->scheduledAt())->toBe($scheduledAt)
        ->and($session->plannedDurationMinutes())->toBe(45)
        ->and($session->status())->toBe(SimulationSessionStatus::Completed)
        ->and($session->startedAt())->toBe($startedAt)
        ->and($session->endedAt())->toBe($endedAt)
        ->and($session->actualDurationMinutes())->toBe(45)
        ->and($session->history())->toBe([$historyEntry]);
});
