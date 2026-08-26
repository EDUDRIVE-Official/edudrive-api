<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Enums\DecisionEvaluationOutcome;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\Services\DecisionEngineCalculator;

function newDecisionPointOf(
    string $sessionId,
    DecisionRiskLevel $riskLevel,
    DriverReactionType $reaction,
    string $roadContext = 'Semáforo en amarillo',
): DecisionPoint {
    return DecisionPoint::record(
        id: (string) Str::uuid(),
        sessionId: $sessionId,
        roadContext: $roadContext,
        riskLevel: $riskLevel,
        driverReaction: $reaction,
        occurredAt: new DateTimeImmutable('2026-09-01T10:12:00+00:00'),
    );
}

it('reporta consistencia 1.0 y sin evaluaciones cuando no hay puntos de decision', function (): void {
    $result = (new DecisionEngineCalculator)->calculate((string) Str::uuid(), []);

    expect($result->evaluations)->toBe([])
        ->and($result->appropriateCount)->toBe(0)
        ->and($result->inappropriateCount)->toBe(0)
        ->and($result->consistencyScore)->toBe(1.0);
});

it('evalua frenar ante riesgo alto como apropiado', function (): void {
    $sessionId = (string) Str::uuid();
    $point = newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Braked);

    $result = (new DecisionEngineCalculator)->calculate($sessionId, [$point]);

    expect($result->evaluations[0]->outcome)->toBe(DecisionEvaluationOutcome::Appropriate)
        ->and($result->appropriateCount)->toBe(1)
        ->and($result->inappropriateCount)->toBe(0)
        ->and($result->consistencyScore)->toBe(1.0);
});

it('evalua acelerar ante riesgo alto como inapropiado', function (): void {
    $sessionId = (string) Str::uuid();
    $point = newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Accelerated);

    $result = (new DecisionEngineCalculator)->calculate($sessionId, [$point]);

    expect($result->evaluations[0]->outcome)->toBe(DecisionEvaluationOutcome::Inappropriate)
        ->and($result->inappropriateCount)->toBe(1);
});

it('ignorar la situacion siempre es inapropiado sin importar el riesgo', function (): void {
    $sessionId = (string) Str::uuid();
    $point = newDecisionPointOf($sessionId, DecisionRiskLevel::Low, DriverReactionType::Ignored);

    $result = (new DecisionEngineCalculator)->calculate($sessionId, [$point]);

    expect($result->evaluations[0]->outcome)->toBe(DecisionEvaluationOutcome::Inappropriate);
});

it('mantener la velocidad es apropiado ante riesgo medio pero no ante riesgo alto', function (): void {
    $sessionId = (string) Str::uuid();
    $medium = newDecisionPointOf($sessionId, DecisionRiskLevel::Medium, DriverReactionType::Maintained);
    $high = newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Maintained);

    $result = (new DecisionEngineCalculator)->calculate($sessionId, [$medium, $high]);

    expect($result->evaluations[0]->outcome)->toBe(DecisionEvaluationOutcome::Appropriate)
        ->and($result->evaluations[1]->outcome)->toBe(DecisionEvaluationOutcome::Inappropriate);
});

it('cualquier reaccion salvo ignorar es apropiada ante riesgo bajo', function (): void {
    $sessionId = (string) Str::uuid();
    $point = newDecisionPointOf($sessionId, DecisionRiskLevel::Low, DriverReactionType::Accelerated);

    $result = (new DecisionEngineCalculator)->calculate($sessionId, [$point]);

    expect($result->evaluations[0]->outcome)->toBe(DecisionEvaluationOutcome::Appropriate);
});

it('reporta consistencia total cuando todas las reacciones ante el mismo riesgo comparten resultado', function (): void {
    $sessionId = (string) Str::uuid();
    $points = [
        newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Braked),
        newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Swerved),
        newDecisionPointOf($sessionId, DecisionRiskLevel::Low, DriverReactionType::Accelerated),
    ];

    $result = (new DecisionEngineCalculator)->calculate($sessionId, $points);

    expect($result->consistencyScore)->toBe(1.0);
});

it('reporta consistencia parcial cuando un grupo de riesgo mezcla resultados', function (): void {
    $sessionId = (string) Str::uuid();
    $points = [
        newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Braked),
        newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Accelerated),
        newDecisionPointOf($sessionId, DecisionRiskLevel::Low, DriverReactionType::Signaled),
    ];

    $result = (new DecisionEngineCalculator)->calculate($sessionId, $points);

    expect($result->consistencyScore)->toBe(0.5);
});

it('incluye retroalimentacion para cada evaluacion', function (): void {
    $sessionId = (string) Str::uuid();
    $point = newDecisionPointOf($sessionId, DecisionRiskLevel::High, DriverReactionType::Ignored);

    $result = (new DecisionEngineCalculator)->calculate($sessionId, [$point]);

    expect($result->evaluations[0]->feedback)->toBeString()
        ->and($result->evaluations[0]->feedback)->not->toBe('');
});
