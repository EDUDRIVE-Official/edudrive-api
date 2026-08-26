<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Application\Exceptions\PracticalResultNotAvailable;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Queries\GetPracticalResultQuery;
use Modules\Simulation\Application\Responses\PracticalResultResponse;
use Modules\Simulation\Application\UseCases\GetPracticalResultHandler;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final class InMemoryPracticalResultSessionRepository implements SimulationSessionRepository
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

final class InMemoryPracticalResultEventRepository implements TelemetryEventRepository
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

function newPracticalResultTestSession(
    InMemoryPracticalResultSessionRepository $repository,
    ?string $userId = null,
    bool $completed = true,
): SimulationSession {
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId ?? (string) Str::uuid(),
        simulatorId: (string) Str::uuid(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );

    if ($completed) {
        $session->start(new DateTimeImmutable('2026-09-01T10:00:00+00:00'));
        $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    }

    $repository->save($session);

    return $session;
}

it('calcula el resultado practico de una sesion completada', function (): void {
    $sessions = new InMemoryPracticalResultSessionRepository;
    $events = new InMemoryPracticalResultEventRepository;
    $userId = (string) Str::uuid();
    $session = newPracticalResultTestSession($sessions, $userId);
    $events->saveBatch([TelemetryEvent::record((string) Str::uuid(), $session->id()->value(), TelemetryEventType::Infraction, null, new DateTimeImmutable('2026-09-01T10:12:00+00:00'))]);

    $response = (new GetPracticalResultHandler($sessions, $events))->handle(new GetPracticalResultQuery($session->id()->value(), $userId, false));

    expect($response)->toBeInstanceOf(PracticalResultResponse::class)
        ->and($response->sessionId)->toBe($session->id()->value())
        ->and($response->outcome)->toBe('passed')
        ->and($response->score)->toBe(90)
        ->and($response->errors)->toHaveCount(1);
});

it('rechaza consultar el resultado de una sesion que no ha finalizado', function (): void {
    $sessions = new InMemoryPracticalResultSessionRepository;
    $events = new InMemoryPracticalResultEventRepository;
    $userId = (string) Str::uuid();
    $session = newPracticalResultTestSession($sessions, $userId, completed: false);

    expect(fn () => (new GetPracticalResultHandler($sessions, $events))->handle(new GetPracticalResultQuery($session->id()->value(), $userId, false)))
        ->toThrow(PracticalResultNotAvailable::class);
});

it('rechaza consultar el resultado de una sesion inexistente', function (): void {
    $sessions = new InMemoryPracticalResultSessionRepository;
    $events = new InMemoryPracticalResultEventRepository;

    expect(fn () => (new GetPracticalResultHandler($sessions, $events))->handle(new GetPracticalResultQuery((string) Str::uuid(), (string) Str::uuid(), true)))
        ->toThrow(SimulationSessionNotFound::class);
});

it('rechaza consultar el resultado de una sesion ajena sin permiso ampliado', function (): void {
    $sessions = new InMemoryPracticalResultSessionRepository;
    $events = new InMemoryPracticalResultEventRepository;
    $session = newPracticalResultTestSession($sessions);

    expect(fn () => (new GetPracticalResultHandler($sessions, $events))->handle(new GetPracticalResultQuery($session->id()->value(), (string) Str::uuid(), false)))
        ->toThrow(SimulationSessionNotFound::class);
});

it('permite consultar el resultado de una sesion ajena con permiso ampliado', function (): void {
    $sessions = new InMemoryPracticalResultSessionRepository;
    $events = new InMemoryPracticalResultEventRepository;
    $session = newPracticalResultTestSession($sessions);

    $response = (new GetPracticalResultHandler($sessions, $events))->handle(new GetPracticalResultQuery($session->id()->value(), (string) Str::uuid(), true));

    expect($response->sessionId)->toBe($session->id()->value());
});
