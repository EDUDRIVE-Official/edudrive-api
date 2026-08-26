<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class TelemetrySample
{
    private function __construct(
        private string $id,
        private string $sessionId,
        private float $speedKph,
        private float $brakingPercentage,
        private float $accelerationMps2,
        private float $steeringAngleDegrees,
        private DateTimeImmutable $recordedAt,
    ) {}

    public static function record(
        string $id,
        string $sessionId,
        float $speedKph,
        float $brakingPercentage,
        float $accelerationMps2,
        float $steeringAngleDegrees,
        DateTimeImmutable $recordedAt,
    ): self {
        if ($speedKph < 0) {
            throw new InvalidArgumentException('La velocidad no puede ser negativa.');
        }

        if ($brakingPercentage < 0 || $brakingPercentage > 100) {
            throw new InvalidArgumentException('El porcentaje de frenado debe estar entre 0 y 100.');
        }

        return new self($id, $sessionId, $speedKph, $brakingPercentage, $accelerationMps2, $steeringAngleDegrees, $recordedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function speedKph(): float
    {
        return $this->speedKph;
    }

    public function brakingPercentage(): float
    {
        return $this->brakingPercentage;
    }

    public function accelerationMps2(): float
    {
        return $this->accelerationMps2;
    }

    public function steeringAngleDegrees(): float
    {
        return $this->steeringAngleDegrees;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
