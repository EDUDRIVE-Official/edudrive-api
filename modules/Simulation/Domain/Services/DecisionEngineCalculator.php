<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Services;

use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Enums\DecisionEvaluationOutcome;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\ValueObjects\DecisionEngineResult;
use Modules\Simulation\Domain\ValueObjects\DecisionPointEvaluation;

final class DecisionEngineCalculator
{
    /** @var array<string, list<string>> */
    private const APPROPRIATE_REACTIONS = [
        DecisionRiskLevel::High->value => [
            DriverReactionType::Braked->value,
            DriverReactionType::Swerved->value,
            DriverReactionType::Signaled->value,
        ],
        DecisionRiskLevel::Medium->value => [
            DriverReactionType::Braked->value,
            DriverReactionType::Swerved->value,
            DriverReactionType::Signaled->value,
            DriverReactionType::Maintained->value,
        ],
        DecisionRiskLevel::Low->value => [
            DriverReactionType::Braked->value,
            DriverReactionType::Swerved->value,
            DriverReactionType::Signaled->value,
            DriverReactionType::Maintained->value,
            DriverReactionType::Accelerated->value,
        ],
    ];

    /** @param list<DecisionPoint> $points */
    public function calculate(string $sessionId, array $points): DecisionEngineResult
    {
        $evaluations = array_map(
            fn (DecisionPoint $point): DecisionPointEvaluation => $this->evaluate($point),
            $points,
        );

        $appropriateCount = 0;
        $inappropriateCount = 0;

        foreach ($evaluations as $evaluation) {
            if ($evaluation->outcome === DecisionEvaluationOutcome::Appropriate) {
                $appropriateCount++;
            } else {
                $inappropriateCount++;
            }
        }

        return new DecisionEngineResult(
            sessionId: $sessionId,
            evaluations: $evaluations,
            appropriateCount: $appropriateCount,
            inappropriateCount: $inappropriateCount,
            consistencyScore: self::consistencyScoreFor($evaluations),
        );
    }

    private function evaluate(DecisionPoint $point): DecisionPointEvaluation
    {
        $appropriateReactions = self::APPROPRIATE_REACTIONS[$point->riskLevel()->value];
        $outcome = in_array($point->driverReaction()->value, $appropriateReactions, true)
            ? DecisionEvaluationOutcome::Appropriate
            : DecisionEvaluationOutcome::Inappropriate;

        return new DecisionPointEvaluation(
            roadContext: $point->roadContext(),
            riskLevel: $point->riskLevel(),
            driverReaction: $point->driverReaction(),
            outcome: $outcome,
            feedback: self::feedbackFor($point->riskLevel(), $outcome),
            occurredAt: $point->occurredAt(),
        );
    }

    private static function feedbackFor(DecisionRiskLevel $riskLevel, DecisionEvaluationOutcome $outcome): string
    {
        if ($outcome === DecisionEvaluationOutcome::Appropriate) {
            return 'Buena decisión: la reacción fue adecuada para el nivel de riesgo de la situación.';
        }

        return match ($riskLevel) {
            DecisionRiskLevel::High => 'En situaciones de alto riesgo se espera una reacción defensiva inmediata (frenar, maniobrar o señalizar); mantener la velocidad, acelerar o ignorar la situación aumenta el peligro.',
            DecisionRiskLevel::Medium => 'Ante riesgo moderado se recomienda anticipar con una reacción defensiva; acelerar o ignorar la situación no es lo adecuado.',
            DecisionRiskLevel::Low => 'Aún en situaciones de bajo riesgo es importante no ignorar el contexto vial.',
        };
    }

    /** @param list<DecisionPointEvaluation> $evaluations */
    private static function consistencyScoreFor(array $evaluations): float
    {
        if ($evaluations === []) {
            return 1.0;
        }

        $groups = [];
        foreach ($evaluations as $evaluation) {
            $groups[$evaluation->riskLevel->value][] = $evaluation->outcome->value;
        }

        $consistentGroups = 0;
        foreach ($groups as $outcomes) {
            if (count(array_unique($outcomes)) === 1) {
                $consistentGroups++;
            }
        }

        return $consistentGroups / count($groups);
    }
}
