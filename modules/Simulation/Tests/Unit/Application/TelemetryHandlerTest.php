<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Application\Commands\SubmitTelemetryCommand;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotInProgress;
use Modules\Simulation\Application\Queries\GetSessionTelemetryQuery;
use Modules\Simulation\Application\Responses\TelemetryBatchResponse;
use Modules\Simulation\Application\Responses\TelemetryResponse;
use Modules\Simulation\Application\UseCases\GetSessionTelemetryHandler;
use Modules\Simulation\Application\UseCases\SubmitTelemetryHandler;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Entities\TelemetrySample;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Repositories\TelemetrySampleRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final class InMemoryTelemetrySessionRepository implements SimulationSessionRepository
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

final class InMemoryTelemetrySampleRepository implements TelemetrySampleRepository
{
    /** @var list<TelemetrySample> */
    public array $items = [];

    /** @param list<TelemetrySample> $samples */
    public function saveBatch(array $samples): int
    {
        $inserted = 0;

        foreach ($samples as $sample) {
            if ($this->hasId($sample->id())) {
                continue;
            }

            $this->items[] = $sample;
            $inserted++;
        }

        return $inserted;
    }

    /** @return list<TelemetrySample> */
    public function allForSession(string $sessionId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (TelemetrySample $sample): bool => $sample->sessionId() === $sessionId,
        ));
    }

    private function hasId(string $id): bool
    {
        foreach ($this->items as $item) {
            if ($item->id() === $id) {
                return true;
            }
        }

        return false;
    }
}

final class InMemoryTelemetryEventRepository implements TelemetryEventRepository
{
    /** @var list<TelemetryEvent> */
    public array $items = [];

    /** @param list<TelemetryEvent> $events */
    public function saveBatch(array $events): int
    {
        $inserted = 0;

        foreach ($events as $event) {
            if ($this->hasId($event->id())) {
                continue;
            }

            $this->items[] = $event;
            $inserted++;
        }

        return $inserted;
    }

    private function hasId(string $id): bool
    {
        foreach ($this->items as $item) {
            if ($item->id() === $id) {
                return true;
            }
        }

        return false;
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

function persistedTelemetryTestSession(InMemoryTelemetrySessionRepository $repository, string $simulatorId, bool $inProgress = true): SimulationSession
{
    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
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

it('registra un lote de lecturas y eventos para una sesion en curso', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId);

    $response = (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [
            ['id' => (string) Str::uuid(), 'speed_kph' => 40.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 1.1, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T10:10:00+00:00'],
            ['id' => (string) Str::uuid(), 'speed_kph' => 38.0, 'braking_percentage' => 20.0, 'acceleration_mps2' => -1.5, 'steering_angle_degrees' => 2.0, 'recorded_at' => '2026-09-01T10:10:05+00:00'],
        ],
        events: [
            ['id' => (string) Str::uuid(), 'type' => 'collision', 'details' => 'Colision leve', 'occurred_at' => '2026-09-01T10:11:00+00:00'],
        ],
    ));

    expect($response)->toBeInstanceOf(TelemetryBatchResponse::class)
        ->and($response->samplesRecorded)->toBe(2)
        ->and($response->eventsRecorded)->toBe(1)
        ->and($samples->allForSession($session->id()->value()))->toHaveCount(2)
        ->and($events->allForSession($session->id()->value()))->toHaveCount(1);
});

it('ignora un reenvio con los mismos identificadores sin duplicar filas', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId);
    $sampleId = (string) Str::uuid();
    $eventId = (string) Str::uuid();
    $command = new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [['id' => $sampleId, 'speed_kph' => 40.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 1.1, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T10:10:00+00:00']],
        events: [['id' => $eventId, 'type' => 'collision', 'details' => null, 'occurred_at' => '2026-09-01T10:11:00+00:00']],
    );
    $handler = new SubmitTelemetryHandler($sessions, $samples, $events);

    $first = $handler->handle($command);
    $second = $handler->handle($command);

    expect($first->samplesRecorded)->toBe(1)
        ->and($first->eventsRecorded)->toBe(1)
        ->and($second->samplesRecorded)->toBe(0)
        ->and($second->eventsRecorded)->toBe(0)
        ->and($samples->allForSession($session->id()->value()))->toHaveCount(1)
        ->and($events->allForSession($session->id()->value()))->toHaveCount(1);
});

it('acepta telemetria que llego tarde pero ocurrio dentro de la ventana real de una sesion completada', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId);
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    $sessions->save($session);

    $response = (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [['id' => (string) Str::uuid(), 'speed_kph' => 40.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 1.1, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T10:20:00+00:00']],
        events: [],
    ));

    expect($response->samplesRecorded)->toBe(1);
});

it('rechaza telemetria cuya marca de tiempo cae fuera de la ventana real de la sesion', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId);
    $session->complete(new DateTimeImmutable('2026-09-01T10:45:00+00:00'));
    $sessions->save($session);

    expect(fn () => (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [['id' => (string) Str::uuid(), 'speed_kph' => 40.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 1.1, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T11:00:00+00:00']],
        events: [],
    )))->toThrow(SimulationSessionNotInProgress::class);
});

it('acepta un lote sin eventos o sin lecturas', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId);

    $response = (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [],
        events: [],
    ));

    expect($response->samplesRecorded)->toBe(0)
        ->and($response->eventsRecorded)->toBe(0);
});

it('rechaza telemetria para una sesion inexistente', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;

    expect(fn () => (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: (string) Str::uuid(),
        simulatorId: (string) Str::uuid(),
        samples: [],
        events: [],
    )))->toThrow(SimulationSessionNotFound::class);
});

it('rechaza telemetria de un simulador distinto al de la sesion', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $session = persistedTelemetryTestSession($sessions, (string) Str::uuid());

    expect(fn () => (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: (string) Str::uuid(),
        samples: [],
        events: [],
    )))->toThrow(SimulationSessionNotFound::class);
});

it('rechaza telemetria para una sesion que no esta en curso', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId, inProgress: false);

    expect(fn () => (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [],
        events: [],
    )))->toThrow(SimulationSessionNotInProgress::class);
});

it('devuelve la telemetria de la sesion al dueno o a un tercero con permiso ampliado', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $simulatorId = (string) Str::uuid();
    $session = persistedTelemetryTestSession($sessions, $simulatorId);
    (new SubmitTelemetryHandler($sessions, $samples, $events))->handle(new SubmitTelemetryCommand(
        sessionId: $session->id()->value(),
        simulatorId: $simulatorId,
        samples: [['id' => (string) Str::uuid(), 'speed_kph' => 30.0, 'braking_percentage' => 0.0, 'acceleration_mps2' => 0.0, 'steering_angle_degrees' => 0.0, 'recorded_at' => '2026-09-01T10:10:00+00:00']],
        events: [],
    ));

    $handler = new GetSessionTelemetryHandler($sessions, $samples, $events);

    $own = $handler->handle(new GetSessionTelemetryQuery($session->id()->value(), $session->userId(), false));
    expect($own)->toBeInstanceOf(TelemetryResponse::class)
        ->and($own->samples)->toHaveCount(1);

    $others = $handler->handle(new GetSessionTelemetryQuery($session->id()->value(), (string) Str::uuid(), true));
    expect($others->samples)->toHaveCount(1);
});

it('rechaza consultar la telemetria de una sesion ajena sin permiso ampliado', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;
    $session = persistedTelemetryTestSession($sessions, (string) Str::uuid());

    expect(fn () => (new GetSessionTelemetryHandler($sessions, $samples, $events))->handle(new GetSessionTelemetryQuery($session->id()->value(), (string) Str::uuid(), false)))
        ->toThrow(SimulationSessionNotFound::class);
});

it('rechaza consultar la telemetria de una sesion inexistente', function (): void {
    $sessions = new InMemoryTelemetrySessionRepository;
    $samples = new InMemoryTelemetrySampleRepository;
    $events = new InMemoryTelemetryEventRepository;

    expect(fn () => (new GetSessionTelemetryHandler($sessions, $samples, $events))->handle(new GetSessionTelemetryQuery((string) Str::uuid(), (string) Str::uuid(), true)))
        ->toThrow(SimulationSessionNotFound::class);
});
