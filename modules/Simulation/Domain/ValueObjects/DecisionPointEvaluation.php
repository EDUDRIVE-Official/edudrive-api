<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Simulation\Domain\Enums\DecisionEvaluationOutcome;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;

final readonly class DecisionPointEvaluation
{
    public function __construct(
        public string $roadContext,
        public DecisionRiskLevel $riskLevel,
        public DriverReactionType $driverReaction,
        public DecisionEvaluationOutcome $outcome,
        public string $feedback,
        public DateTimeImmutable $occurredAt,
    ) {}
}
