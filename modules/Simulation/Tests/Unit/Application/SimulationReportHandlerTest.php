<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Application\Queries\GetUserEvolutionReportQuery;
use Modules\Simulation\Application\Queries\GetUserRiskReportQuery;
use Modules\Simulation\Application\Queries\GetUserSessionsReportQuery;
use Modules\Simulation\Application\Queries\GetUserTelemetryReportQuery;
use Modules\Simulation\Application\Responses\UserEvolutionReportResponse;
use Modules\Simulation\Application\Responses\UserRiskReportResponse;
use Modules\Simulation\Application\Responses\UserSessionsReportResponse;
use Modules\Simulation\Application\Responses\UserTelemetryReportResponse;
use Modules\Simulation\Application\Services\ReportUserIdsResolver;
use Modules\Simulation\Application\UseCases\GetUserEvolutionReportHandler;
use Modules\Simulation\Application\UseCases\GetUserRiskReportHandler;
use Modules\Simulation\Application\UseCases\GetUserSessionsReportHandler;
use Modules\Simulation\Application\UseCases\GetUserTelemetryReportHandler;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final class InMemoryReportSessionRepository implements SimulationSessionRepository
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

final class InMemoryReportEventRepository implements TelemetryEventRepository
{
    /** @var list<TelemetryEvent> */
    public array $items = [];

    /** @param list<TelemetryEvent> $events */
    public function saveBatch(array $events): int
    {
        foreach ($events as $event) {
            $this->items[] = $event;
        }

        return count($events);
    }

    /** @return list<TelemetryEvent> */
    public function allForSession(string $sessionId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (TelemetryEvent $event): bool => $event->sessionId() === $sessionId,
        ));
    }
}

final class InMemoryReportDecisionPointRepository implements DecisionPointRepository
{
    /** @var list<DecisionPoint> */
    public array $items = [];

    /** @param list<DecisionPoint> $points */
    public function saveBatch(array $points): int
    {
        foreach ($points as $point) {
            $this->items[] = $point;
        }

        return count($points);
    }

    /** @return list<DecisionPoint> */
    public function allForSession(string $sessionId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (DecisionPoint $point): bool => $point->sessionId() === $sessionId,
        ));
    }
}

function newReportCompletedSession(InMemoryReportSessionRepository $repository, string $userId): SimulationSession
{
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId,
        simulatorId: (string) Str::uuid(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
    $session->start(new DateTimeImmutable('2026-09-01T10:00:00+00:00'));
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    $repository->save($session);

    return $session;
}

it('agrega las sesiones de un usuario', function (): void {
    $sessions = new InMemoryReportSessionRepository;
    $userId = (string) Str::uuid();
    newReportCompletedSession($sessions, $userId);

    $cancelled = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId,
        simulatorId: (string) Str::uuid(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-02T10:00:00+00:00'),
        plannedDurationMinutes: 30,
    );
    $cancelled->cancel('sin disponibilidad', new DateTimeImmutable('2026-09-02T09:00:00+00:00'));
    $sessions->save($cancelled);

    $handler = new GetUserSessionsReportHandler(new ReportUserIdsResolver($sessions), $sessions);
    $reports = $handler->handle(new GetUserSessionsReportQuery(userIds: [$userId]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(UserSessionsReportResponse::class)
        ->and($reports[0]->sessionCount)->toBe(2)
        ->and($reports[0]->completedCount)->toBe(1)
        ->and($reports[0]->cancelledCount)->toBe(1)
        ->and($reports[0]->averageDurationMinutes)->toBe(45.0);
});

it('agrega errores e infracciones por tipo de evento', function (): void {
    $sessions = new InMemoryReportSessionRepository;
    $events = new InMemoryReportEventRepository;
    $userId = (string) Str::uuid();
    $session = newReportCompletedSession($sessions, $userId);

    $events->saveBatch([
        TelemetryEvent::record((string) Str::uuid(), $session->id()->value(), TelemetryEventType::Infraction, null, new DateTimeImmutable),
        TelemetryEvent::record((string) Str::uuid(), $session->id()->value(), TelemetryEventType::Infraction, null, new DateTimeImmutable),
        TelemetryEvent::record((string) Str::uuid(), $session->id()->value(), TelemetryEventType::Collision, null, new DateTimeImmutable),
    ]);

    $handler = new GetUserTelemetryReportHandler(new ReportUserIdsResolver($sessions), $sessions, $events);
    $reports = $handler->handle(new GetUserTelemetryReportQuery(userIds: [$userId]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(UserTelemetryReportResponse::class)
        ->and($reports[0]->totalEvents)->toBe(3)
        ->and($reports[0]->eventCountsByType['infraction'])->toBe(2)
        ->and($reports[0]->eventCountsByType['collision'])->toBe(1)
        ->and($reports[0]->eventCountsByType['signal_usage'])->toBe(0);
});

it('agrega la evolucion cronologica de resultados de un usuario', function (): void {
    $sessions = new InMemoryReportSessionRepository;
    $events = new InMemoryReportEventRepository;
    $userId = (string) Str::uuid();
    $session = newReportCompletedSession($sessions, $userId);

    $handler = new GetUserEvolutionReportHandler(new ReportUserIdsResolver($sessions), $sessions, $events);
    $reports = $handler->handle(new GetUserEvolutionReportQuery(userIds: [$userId]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(UserEvolutionReportResponse::class)
        ->and($reports[0]->entries)->toHaveCount(1)
        ->and($reports[0]->entries[0]['session_id'])->toBe($session->id()->value())
        ->and($reports[0]->entries[0]['outcome'])->toBe('passed')
        ->and($reports[0]->entries[0]['score'])->toBe(100);
});

it('agrega los riesgos detectados de un usuario', function (): void {
    $sessions = new InMemoryReportSessionRepository;
    $decisionPoints = new InMemoryReportDecisionPointRepository;
    $userId = (string) Str::uuid();
    $session = newReportCompletedSession($sessions, $userId);

    $decisionPoints->saveBatch([
        DecisionPoint::record((string) Str::uuid(), $session->id()->value(), 'cruce peatonal', DecisionRiskLevel::High, DriverReactionType::Accelerated, new DateTimeImmutable),
        DecisionPoint::record((string) Str::uuid(), $session->id()->value(), 'curva cerrada', DecisionRiskLevel::High, DriverReactionType::Braked, new DateTimeImmutable),
    ]);

    $handler = new GetUserRiskReportHandler(new ReportUserIdsResolver($sessions), $sessions, $decisionPoints);
    $reports = $handler->handle(new GetUserRiskReportQuery(userIds: [$userId]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(UserRiskReportResponse::class)
        ->and($reports[0]->totalDecisionPoints)->toBe(2)
        ->and($reports[0]->appropriateCount)->toBe(1)
        ->and($reports[0]->inappropriateCount)->toBe(1)
        ->and($reports[0]->inappropriateByRiskLevel['high'])->toBe(1);
});

it('descubre todos los usuarios cuando no se especifican user_ids', function (): void {
    $sessions = new InMemoryReportSessionRepository;
    newReportCompletedSession($sessions, (string) Str::uuid());

    $reports = (new GetUserSessionsReportHandler(new ReportUserIdsResolver($sessions), $sessions))
        ->handle(new GetUserSessionsReportQuery);

    expect(count($reports))->toBeGreaterThanOrEqual(1);
});
