<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\PracticalResultOutcome;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Services\PracticalResultCalculator;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

function newCompletedSession(string $scenario = 'circuito-urbano'): SimulationSession
{
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        simulatorId: (string) Str::uuid(),
        vehicleType: 'sedan',
        scenario: $scenario,
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
    $session->start(new DateTimeImmutable('2026-09-01T10:00:00+00:00'));
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));

    return $session;
}

function newTelemetryEventOf(string $sessionId, TelemetryEventType $type, ?string $details = null): TelemetryEvent
{
    return TelemetryEvent::record(
        id: (string) Str::uuid(),
        sessionId: $sessionId,
        type: $type,
        details: $details,
        occurredAt: new DateTimeImmutable('2026-09-01T10:12:00+00:00'),
    );
}

it('aprueba con puntaje 100 y una competencia demostrada cuando no hay eventos', function (): void {
    $session = newCompletedSession();

    $result = (new PracticalResultCalculator)->calculate($session, []);

    expect($result->outcome)->toBe(PracticalResultOutcome::Passed)
        ->and($result->score)->toBe(100)
        ->and($result->totalPenaltyPoints)->toBe(0)
        ->and($result->errors)->toBe([])
        ->and($result->competenciesDemonstrated)->toBe(['Conducción en escenario: circuito-urbano'])
        ->and($result->recommendations)->toBe([]);
});

it('penaliza una colision con 30 puntos y sigue aprobando en el limite', function (): void {
    $session = newCompletedSession();
    $events = [newTelemetryEventOf($session->id()->value(), TelemetryEventType::Collision, 'Colision leve')];

    $result = (new PracticalResultCalculator)->calculate($session, $events);

    expect($result->score)->toBe(70)
        ->and($result->outcome)->toBe(PracticalResultOutcome::Passed)
        ->and($result->totalPenaltyPoints)->toBe(30)
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->type)->toBe(TelemetryEventType::Collision)
        ->and($result->errors[0]->penaltyPoints)->toBe(30)
        ->and($result->errors[0]->details)->toBe('Colision leve');
});

it('reprueba cuando la penalizacion baja el puntaje por debajo del umbral', function (): void {
    $session = newCompletedSession();
    $events = [
        newTelemetryEventOf($session->id()->value(), TelemetryEventType::Collision),
        newTelemetryEventOf($session->id()->value(), TelemetryEventType::Infraction),
    ];

    $result = (new PracticalResultCalculator)->calculate($session, $events);

    expect($result->score)->toBe(60)
        ->and($result->outcome)->toBe(PracticalResultOutcome::Failed)
        ->and($result->competenciesDemonstrated)->toBe([]);
});

it('no deja que el puntaje baje de cero', function (): void {
    $session = newCompletedSession();
    $events = array_fill(0, 5, newTelemetryEventOf($session->id()->value(), TelemetryEventType::Collision));

    $result = (new PracticalResultCalculator)->calculate($session, $events);

    expect($result->score)->toBe(0)
        ->and($result->outcome)->toBe(PracticalResultOutcome::Failed);
});

it('no penaliza el uso de senales', function (): void {
    $session = newCompletedSession();
    $events = [newTelemetryEventOf($session->id()->value(), TelemetryEventType::SignalUsage)];

    $result = (new PracticalResultCalculator)->calculate($session, $events);

    expect($result->score)->toBe(100)
        ->and($result->errors)->toBe([]);
});

it('genera una recomendacion por cada tipo de error sin duplicados', function (): void {
    $session = newCompletedSession();
    $events = [
        newTelemetryEventOf($session->id()->value(), TelemetryEventType::Infraction),
        newTelemetryEventOf($session->id()->value(), TelemetryEventType::Infraction),
        newTelemetryEventOf($session->id()->value(), TelemetryEventType::Critical),
    ];

    $result = (new PracticalResultCalculator)->calculate($session, $events);

    expect($result->recommendations)->toHaveCount(2);
});
