<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\GradingResult;
use Modules\Academic\Domain\ValueObjects\QuestionId;

function gradingResultQuestionGrade(): AttemptQuestionGrade
{
    return new AttemptQuestionGrade(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92111'),
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92113'),
        8,
        10,
        80,
        false,
        true,
    );
}

function gradingResultCompetencyGrade(): CompetencyGrade
{
    return new CompetencyGrade(
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92113'),
        8,
        10,
        80,
    );
}

it('construye un resultado de calificacion con breakdowns', function (): void {
    $result = new GradingResult(
        8,
        10,
        80,
        true,
        [gradingResultQuestionGrade()],
        [gradingResultCompetencyGrade()],
    );

    expect($result->score())->toBe(8)
        ->and($result->totalPoints())->toBe(10)
        ->and($result->percentage())->toBe(80)
        ->and($result->passed())->toBeTrue()
        ->and($result->questionBreakdown())->toHaveCount(1)
        ->and($result->competencyBreakdown())->toHaveCount(1)
        ->and($result->toArray())->toBe([
            'score' => 8,
            'total_points' => 10,
            'percentage' => 80,
            'passed' => true,
            'question_breakdown' => [gradingResultQuestionGrade()->toArray()],
            'competency_breakdown' => [gradingResultCompetencyGrade()->toArray()],
        ]);
});

it('rechaza un resultado de calificacion inconsistente', function (): void {
    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        120,
        true,
        [gradingResultQuestionGrade()],
        [gradingResultCompetencyGrade()],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        80,
        true,
        [],
        [gradingResultCompetencyGrade()],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        80,
        true,
        [gradingResultQuestionGrade()],
        [],
    ))->toThrow(InvalidArgumentException::class);
});

it('rechaza breakdowns con tipos invalidos en runtime', function (): void {
    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        80,
        true,
        ['invalid'],
        [gradingResultCompetencyGrade()],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        80,
        true,
        [gradingResultQuestionGrade()],
        ['invalid'],
    ))->toThrow(InvalidArgumentException::class);
});

it('rechaza totales que no coinciden con los breakdowns', function (): void {
    expect(fn (): GradingResult => new GradingResult(
        9,
        10,
        80,
        true,
        [gradingResultQuestionGrade()],
        [gradingResultCompetencyGrade()],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        11,
        80,
        true,
        [gradingResultQuestionGrade()],
        [gradingResultCompetencyGrade()],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        81,
        true,
        [gradingResultQuestionGrade()],
        [gradingResultCompetencyGrade()],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        80,
        true,
        [gradingResultQuestionGrade()],
        [new CompetencyGrade(
            CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92113'),
            7,
            10,
            70,
        )],
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): GradingResult => new GradingResult(
        8,
        10,
        80,
        true,
        [gradingResultQuestionGrade()],
        [new CompetencyGrade(
            CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92113'),
            8,
            9,
            89,
        )],
    ))->toThrow(InvalidArgumentException::class);
});
