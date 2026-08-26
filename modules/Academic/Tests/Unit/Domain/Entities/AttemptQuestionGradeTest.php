<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

it('construye un breakdown de calificacion por pregunta', function (): void {
    $grade = new AttemptQuestionGrade(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        8,
        10,
        80,
        false,
        true,
    );

    expect($grade->score())->toBe(8)
        ->and($grade->totalPoints())->toBe(10)
        ->and($grade->percentage())->toBe(80)
        ->and($grade->isCorrect())->toBeFalse()
        ->and($grade->isAnswered())->toBeTrue()
        ->and($grade->toArray())->toBe([
            'attempt_question_id' => '01981a64-8300-7b1d-b442-764ea7f92101',
            'question_id' => '01981a64-8300-7b1d-b442-764ea7f92102',
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f92103',
            'score' => 8,
            'total_points' => 10,
            'percentage' => 80,
            'is_correct' => false,
            'is_answered' => true,
        ]);
});

it('rechaza una calificacion por pregunta inconsistente', function (): void {
    expect(fn (): AttemptQuestionGrade => new AttemptQuestionGrade(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        -1,
        10,
        0,
        false,
        false,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): AttemptQuestionGrade => new AttemptQuestionGrade(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        11,
        10,
        110,
        false,
        false,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): AttemptQuestionGrade => new AttemptQuestionGrade(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        8,
        10,
        81,
        false,
        false,
    ))->toThrow(InvalidArgumentException::class);
});
