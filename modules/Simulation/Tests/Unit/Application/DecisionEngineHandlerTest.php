<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Application\Commands\SubmitDecisionPointsCommand;
use Modules\Simulation\Application\Exceptions\DecisionEngineResultNotAvailable;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotInProgress;
use Modules\Simulation\Application\Queries\GetDecisionEngineResultQuery;
use Modules\Simulation\Application\Responses\DecisionEngineResultResponse;
use Modules\Simulation\Application\Responses\DecisionPointsBatchResponse;
use Modules\Simulation\Application\UseCases\GetDecisionEngineResultHandler;
use Modules\Simulation\Application\UseCases\SubmitDecisionPointsHandler;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final class InMemoryDecisionEngineSessionRepository implements SimulationSessionRepository
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
        throw new LogicException('No usado en esta prueba.');
    }

    /** @return list<SimulationSession> */
    public function all(): array
    {
        throw new LogicException('No usado en esta prueba.');
    }
}

final class InMemoryDecisionPointRepository implements DecisionPointRepository
{
    /** @var list<DecisionPoint> */
    public array $items = [];

    /** @param list<DecisionPoint> $points */
    public function saveBatch(array $points): void
    {
        foreach ($points as $point) {
            $this->items[] = $point;
        }
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

function newDecisionEngineTestSession(
    InMemoryDecisionEngineSessionRepository $repository,
    string $simulatorId,
    ?string $userId = null,
    bool $inProgress = true,
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

    if ($inProgress) {
        $session->start(new DateTimeImmutable('2026-09-01T10:00:00+00:00'));
    }

    $repository->save($session);

    return $session;
}

it('registra un lote de puntos de decision para una sesion en curso', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;
    $simulatorId = (string) Str::uuid();
    $session = newDecisionEngineTestSession($sessions, $simulatorId);

    $response = (new SubmitDecisionPointsHandler($sessions, $points))->handle(new SubmitDecisionPointsCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        decisions: [
            ['road_context' => 'Semáforo en amarillo', 'risk_level' => 'high', 'driver_reaction' => 'braked', 'occurred_at' => '2026-09-01T10:12:00+00:00'],
            ['road_context' => 'Peatón cruzando', 'risk_level' => 'medium', 'driver_reaction' => 'signaled', 'occurred_at' => '2026-09-01T10:13:00+00:00'],
        ],
    ));

    expect($response)->toBeInstanceOf(DecisionPointsBatchResponse::class)
        ->and($response->decisionsRecorded)->toBe(2)
        ->and($points->allForSession($session->id()->value()))->toHaveCount(2);
});

it('rechaza puntos de decision para una sesion inexistente', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;

    expect(fn () => (new SubmitDecisionPointsHandler($sessions, $points))->handle(new SubmitDecisionPointsCommand(
        sessionId: (string) Str::uuid(),
        simulatorId: (string) Str::uuid(),
        decisions: [],
    )))->toThrow(SimulationSessionNotFound::class);
});

it('rechaza puntos de decision de un simulador distinto al de la sesion', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;
    $session = newDecisionEngineTestSession($sessions, (string) Str::uuid());

    expect(fn () => (new SubmitDecisionPointsHandler($sessions, $points))->handle(new SubmitDecisionPointsCommand(
        sessionId: $session->id()->value(),
        simulatorId: (string) Str::uuid(),
        decisions: [],
    )))->toThrow(SimulationSessionNotFound::class);
});

it('rechaza puntos de decision para una sesion que no esta en curso', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;
    $simulatorId = (string) Str::uuid();
    $session = newDecisionEngineTestSession($sessions, $simulatorId, inProgress: false);

    expect(fn () => (new SubmitDecisionPointsHandler($sessions, $points))->handle(new SubmitDecisionPointsCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        decisions: [],
    )))->toThrow(SimulationSessionNotInProgress::class);
});

it('calcula el resultado del motor de decisiones de una sesion completada', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;
    $simulatorId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $session = newDecisionEngineTestSession($sessions, $simulatorId, $userId);
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    $sessions->save($session);
    $points->saveBatch([
        DecisionPoint::record((string) Str::uuid(), $session->id()->value(), 'Semáforo en amarillo', DecisionRiskLevel::High, DriverReactionType::Braked, new DateTimeImmutable('2026-09-01T10:12:00+00:00')),
    ]);

    $response = (new GetDecisionEngineResultHandler($sessions, $points))->handle(new GetDecisionEngineResultQuery($session->id()->value(), $userId, false));

    expect($response)->toBeInstanceOf(DecisionEngineResultResponse::class)
        ->and($response->sessionId)->toBe($session->id()->value())
        ->and($response->appropriateCount)->toBe(1)
        ->and($response->evaluations)->toHaveCount(1);
});

it('rechaza consultar el resultado del motor de decisiones de una sesion que no ha finalizado', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;
    $simulatorId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $session = newDecisionEngineTestSession($sessions, $simulatorId, $userId);

    expect(fn () => (new GetDecisionEngineResultHandler($sessions, $points))->handle(new GetDecisionEngineResultQuery($session->id()->value(), $userId, false)))
        ->toThrow(DecisionEngineResultNotAvailable::class);
});

it('rechaza consultar el resultado del motor de decisiones de una sesion ajena sin permiso ampliado', function (): void {
    $sessions = new InMemoryDecisionEngineSessionRepository;
    $points = new InMemoryDecisionPointRepository;
    $session = newDecisionEngineTestSession($sessions, (string) Str::uuid());

    expect(fn () => (new GetDecisionEngineResultHandler($sessions, $points))->handle(new GetDecisionEngineResultQuery($session->id()->value(), (string) Str::uuid(), false)))
        ->toThrow(SimulationSessionNotFound::class);
});
