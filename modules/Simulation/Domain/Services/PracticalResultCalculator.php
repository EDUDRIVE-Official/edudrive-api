<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Services;

use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\PracticalResultOutcome;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\ValueObjects\PracticalResult;
use Modules\Simulation\Domain\ValueObjects\PracticalResultError;

final class PracticalResultCalculator
{
    /** @var array<string, int> */
    private const PENALTY_POINTS = [
        TelemetryEventType::Collision->value => 30,
        TelemetryEventType::Infraction->value => 10,
        TelemetryEventType::Critical->value => 20,
    ];

    private const int PASSING_SCORE = 70;

    /** @param list<TelemetryEvent> $events */
    public function calculate(SimulationSession $session, array $events): PracticalResult
    {
        $errors = [];
        $totalPenaltyPoints = 0;

        foreach ($events as $event) {
            $points = self::PENALTY_POINTS[$event->type()->value] ?? 0;

            if ($points === 0) {
                continue;
            }

            $totalPenaltyPoints += $points;
            $errors[] = new PracticalResultError($event->type(), $event->occurredAt(), $points, $event->details());
        }

        $score = max(0, 100 - $totalPenaltyPoints);
        $outcome = $score >= self::PASSING_SCORE ? PracticalResultOutcome::Passed : PracticalResultOutcome::Failed;

        return new PracticalResult(
            sessionId: $session->id()->value(),
            outcome: $outcome,
            score: $score,
            totalPenaltyPoints: $totalPenaltyPoints,
            errors: $errors,
            competenciesDemonstrated: $outcome === PracticalResultOutcome::Passed
                ? [sprintf('Conducción en escenario: %s', $session->scenario())]
                : [],
            recommendations: self::recommendationsFor($errors),
        );
    }

    /**
     * @param  list<PracticalResultError>  $errors
     * @return list<string>
     */
    private static function recommendationsFor(array $errors): array
    {
        $seen = [];
        $recommendations = [];

        foreach ($errors as $error) {
            if (isset($seen[$error->type->value])) {
                continue;
            }

            $seen[$error->type->value] = true;
            $recommendations[] = match ($error->type) {
                TelemetryEventType::Collision => 'Practicar distancia de seguridad y frenado defensivo.',
                TelemetryEventType::Infraction => 'Repasar las normas de tránsito antes de la próxima sesión.',
                TelemetryEventType::Critical => 'Revisar el manejo en situaciones de riesgo alto.',
                TelemetryEventType::SignalUsage => 'Reforzar el uso oportuno de señales.',
            };
        }

        return $recommendations;
    }
}
