<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

use DateTimeInterface;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Entities\TelemetrySample;

final readonly class TelemetryResponse
{
    /**
     * @param  list<array{speed_kph: float, braking_percentage: float, acceleration_mps2: float, steering_angle_degrees: float, recorded_at: string}>  $samples
     * @param  list<array{type: string, details: ?string, occurred_at: string}>  $events
     */
    public function __construct(
        public string $sessionId,
        public array $samples,
        public array $events,
    ) {}

    /**
     * @param  list<TelemetrySample>  $samples
     * @param  list<TelemetryEvent>  $events
     */
    public static function fromSamplesAndEvents(string $sessionId, array $samples, array $events): self
    {
        return new self(
            sessionId: $sessionId,
            samples: array_map(
                static fn (TelemetrySample $sample): array => [
                    'speed_kph' => $sample->speedKph(),
                    'braking_percentage' => $sample->brakingPercentage(),
                    'acceleration_mps2' => $sample->accelerationMps2(),
                    'steering_angle_degrees' => $sample->steeringAngleDegrees(),
                    'recorded_at' => $sample->recordedAt()->format(DateTimeInterface::ATOM),
                ],
                $samples,
            ),
            events: array_map(
                static fn (TelemetryEvent $event): array => [
                    'type' => $event->type()->value,
                    'details' => $event->details(),
                    'occurred_at' => $event->occurredAt()->format(DateTimeInterface::ATOM),
                ],
                $events,
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'samples' => $this->samples,
            'events' => $this->events,
        ];
    }
}
