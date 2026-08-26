<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use DateTimeImmutable;
use Modules\Simulation\Application\Commands\SubmitTelemetryCommand;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotInProgress;
use Modules\Simulation\Application\Responses\TelemetryBatchResponse;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Entities\TelemetrySample;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Repositories\TelemetrySampleRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class SubmitTelemetryHandler
{
    public function __construct(
        private SimulationSessionRepository $sessions,
        private TelemetrySampleRepository $samples,
        private TelemetryEventRepository $events,
    ) {}

    public function handle(SubmitTelemetryCommand $command): TelemetryBatchResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($command->sessionId));
        if ($session === null || $session->simulatorId() !== $command->simulatorId) {
            throw SimulationSessionNotFound::withId($command->sessionId);
        }

        if ($session->startedAt() === null) {
            throw SimulationSessionNotInProgress::create();
        }

        $samples = array_map(
            fn (array $data): TelemetrySample => TelemetrySample::record(
                id: (string) $data['id'],
                sessionId: $command->sessionId,
                speedKph: (float) $data['speed_kph'],
                brakingPercentage: (float) $data['braking_percentage'],
                accelerationMps2: (float) $data['acceleration_mps2'],
                steeringAngleDegrees: (float) $data['steering_angle_degrees'],
                recordedAt: new DateTimeImmutable((string) $data['recorded_at']),
            ),
            $command->samples,
        );

        $events = array_map(
            fn (array $data): TelemetryEvent => TelemetryEvent::record(
                id: (string) $data['id'],
                sessionId: $command->sessionId,
                type: TelemetryEventType::from((string) $data['type']),
                details: $data['details'] ?? null,
                occurredAt: new DateTimeImmutable((string) $data['occurred_at']),
            ),
            $command->events,
        );

        $this->ensureWithinRecordedWindow($session, $samples, $events);

        return new TelemetryBatchResponse(
            $this->samples->saveBatch($samples),
            $this->events->saveBatch($events),
        );
    }

    /**
     * @param  list<TelemetrySample>  $samples
     * @param  list<TelemetryEvent>  $events
     */
    private function ensureWithinRecordedWindow(SimulationSession $session, array $samples, array $events): void
    {
        foreach ($samples as $sample) {
            if (! $session->wasInProgressAt($sample->recordedAt())) {
                throw SimulationSessionNotInProgress::create();
            }
        }

        foreach ($events as $event) {
            if (! $session->wasInProgressAt($event->occurredAt())) {
                throw SimulationSessionNotInProgress::create();
            }
        }
    }
}
