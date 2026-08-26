<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Services\ExamAttemptGrader;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\GradingPolicy;
use Modules\Academic\Domain\ValueObjects\QuestionId;

function examAttemptGraderQuestion(
    string $attemptQuestionId,
    int $position,
    string $questionId,
    string $competencyId,
    int $points,
    QuestionType $type,
    mixed $correctResponse,
): AttemptQuestion {
    return AttemptQuestion::create(
        AttemptQuestionId::fromString($attemptQuestionId),
        $position,
        QuestionId::fromString($questionId),
        CompetencyId::fromString($competencyId),
        $points,
        "Pregunta {$position}",
        $type,
        [],
        $correctResponse,
    );
}

function examAttemptGraderAttempt(array $questions, int $passingScore = 70): ExamAttempt
{
    return ExamAttempt::start(
        ExamAttemptId::fromString('01981a64-8300-7b1d-b442-764ea7f92200'),
        ExamId::fromString('01981a64-8300-7b1d-b442-764ea7f92201'),
        'user-1',
        'Examen de prueba',
        45,
        $passingScore,
        false,
        ExamFeedbackMode::AfterSubmission,
        $questions,
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
}

it('calcula score total, porcentaje, passed y breakdowns cuando todo es correcto', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92211',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92212',
            '01981a64-8300-7b1d-b442-764ea7f92213',
            10,
            QuestionType::SingleChoice,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        ),
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92214',
            2,
            '01981a64-8300-7b1d-b442-764ea7f92215',
            '01981a64-8300-7b1d-b442-764ea7f92213',
            5,
            QuestionType::MultiSelect,
            MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-b', 'opt-c']]),
        ),
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92216',
            3,
            '01981a64-8300-7b1d-b442-764ea7f92217',
            '01981a64-8300-7b1d-b442-764ea7f92218',
            5,
            QuestionType::Matching,
            MatchingResponse::fromArray([
                'type' => 'matching',
                'pairs' => [
                    ['leftId' => 'l1', 'rightId' => 'r1'],
                    ['leftId' => 'l2', 'rightId' => 'r2'],
                ],
            ]),
        ),
    ]);

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->answer(2, MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-c', 'opt-b']]), new DateTimeImmutable('2026-08-12 10:02:00'));
    $attempt->answer(3, MatchingResponse::fromArray([
        'type' => 'matching',
        'pairs' => [
            ['leftId' => 'l2', 'rightId' => 'r2'],
            ['leftId' => 'l1', 'rightId' => 'r1'],
        ],
    ]), new DateTimeImmutable('2026-08-12 10:03:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(false, false));

    expect($result->score())->toBe(20)
        ->and($result->totalPoints())->toBe(20)
        ->and($result->percentage())->toBe(100)
        ->and($result->passed())->toBeTrue()
        ->and($result->questionBreakdown())->toHaveCount(3)
        ->and($result->questionBreakdown()[0]->toArray())->toBe([
            'attempt_question_id' => '01981a64-8300-7b1d-b442-764ea7f92211',
            'question_id' => '01981a64-8300-7b1d-b442-764ea7f92212',
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f92213',
            'score' => 10,
            'total_points' => 10,
            'percentage' => 100,
            'is_correct' => true,
            'is_answered' => true,
        ])
        ->and($result->competencyBreakdown())->toHaveCount(2)
        ->and($result->competencyBreakdown()[0]->toArray())->toBe([
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f92213',
            'score' => 15,
            'total_points' => 15,
            'percentage' => 100,
        ])
        ->and($result->competencyBreakdown()[1]->toArray())->toBe([
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f92218',
            'score' => 5,
            'total_points' => 5,
            'percentage' => 100,
        ]);
});

it('aplica partial credit solo en tipos compatibles y mantiene todo o nada en single_choice', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92221',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92222',
            '01981a64-8300-7b1d-b442-764ea7f92223',
            10,
            QuestionType::MultiSelect,
            MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        ),
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92224',
            2,
            '01981a64-8300-7b1d-b442-764ea7f92225',
            '01981a64-8300-7b1d-b442-764ea7f92226',
            10,
            QuestionType::SingleChoice,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-c']),
        ),
    ], 60);

    $attempt->answer(1, MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a']]), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->answer(2, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-c']), new DateTimeImmutable('2026-08-12 10:02:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(true, false));

    expect($result->score())->toBe(15)
        ->and($result->totalPoints())->toBe(20)
        ->and($result->percentage())->toBe(75)
        ->and($result->passed())->toBeTrue()
        ->and($result->questionBreakdown()[0]->score())->toBe(5)
        ->and($result->questionBreakdown()[0]->percentage())->toBe(50)
        ->and($result->questionBreakdown()[0]->isCorrect())->toBeFalse()
        ->and($result->questionBreakdown()[0]->isAnswered())->toBeTrue()
        ->and($result->questionBreakdown()[1]->score())->toBe(10)
        ->and($result->questionBreakdown()[1]->percentage())->toBe(100)
        ->and($result->questionBreakdown()[1]->isCorrect())->toBeTrue()
        ->and($result->competencyBreakdown()[0]->score())->toBe(5)
        ->and($result->competencyBreakdown()[0]->totalPoints())->toBe(10)
        ->and($result->competencyBreakdown()[1]->score())->toBe(10)
        ->and($result->competencyBreakdown()[1]->totalPoints())->toBe(10);
});

it('ignora applyPenalties y conserva score base no negativo', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92231',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92232',
            '01981a64-8300-7b1d-b442-764ea7f92233',
            10,
            QuestionType::SingleChoice,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        ),
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92234',
            2,
            '01981a64-8300-7b1d-b442-764ea7f92235',
            '01981a64-8300-7b1d-b442-764ea7f92233',
            5,
            QuestionType::SingleChoice,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b']),
        ),
    ]);

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-z']), new DateTimeImmutable('2026-08-12 10:01:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(false, true));

    expect($result->score())->toBe(0)
        ->and($result->totalPoints())->toBe(15)
        ->and($result->percentage())->toBe(0)
        ->and($result->passed())->toBeFalse()
        ->and($result->questionBreakdown()[0]->score())->toBe(0)
        ->and($result->questionBreakdown()[0]->isAnswered())->toBeTrue()
        ->and($result->questionBreakdown()[1]->score())->toBe(0)
        ->and($result->questionBreakdown()[1]->isAnswered())->toBeFalse()
        ->and($result->toArray())->toBe((new ExamAttemptGrader)->grade($attempt, new GradingPolicy(false, false))->toArray())
        ->and($result->competencyBreakdown())->toHaveCount(1)
        ->and($result->competencyBreakdown()[0]->toArray())->toBe([
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f92233',
            'score' => 0,
            'total_points' => 15,
            'percentage' => 0,
        ]);
});

it('calcula porcentaje cero cuando todas las preguntas valen puntos pero ninguna suma score', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92241',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92242',
            '01981a64-8300-7b1d-b442-764ea7f92243',
            5,
            QuestionType::SingleChoice,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        ),
    ]);

    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-z']), new DateTimeImmutable('2026-08-12 10:01:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(false, false));

    expect($result->score())->toBe(0)
        ->and($result->totalPoints())->toBe(5)
        ->and($result->percentage())->toBe(0)
        ->and($result->passed())->toBeFalse();
});

it('otorga partial credit en multi_select y penaliza selecciones invalidas sin bajar de cero', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92251',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92252',
            '01981a64-8300-7b1d-b442-764ea7f92253',
            12,
            QuestionType::MultiSelect,
            MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b', 'opt-c']]),
        ),
    ]);

    $attempt->answer(1, MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-x']]), new DateTimeImmutable('2026-08-12 10:01:00'));

    $grader = new ExamAttemptGrader;

    $withoutPenalty = $grader->grade($attempt, new GradingPolicy(true, false));
    $withPenalty = $grader->grade($attempt, new GradingPolicy(true, true));

    expect($withoutPenalty->score())->toBe(4)
        ->and($withoutPenalty->questionBreakdown()[0]->score())->toBe(4)
        ->and($withoutPenalty->questionBreakdown()[0]->percentage())->toBe(33)
        ->and($withoutPenalty->questionBreakdown()[0]->isCorrect())->toBeFalse()
        ->and($withPenalty->score())->toBe(0)
        ->and($withPenalty->questionBreakdown()[0]->score())->toBe(0)
        ->and($withPenalty->questionBreakdown()[0]->percentage())->toBe(0)
        ->and($withPenalty->questionBreakdown()[0]->isCorrect())->toBeFalse();
});

it('no sobrecuenta ids repetidos en multi_select y mantiene clamp a cero cuando la penalizacion supera el score base', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92266',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92267',
            '01981a64-8300-7b1d-b442-764ea7f92268',
            12,
            QuestionType::MultiSelect,
            MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b', 'opt-c']]),
        ),
    ]);

    $attempt->answer(1, new MultiSelectResponse(['opt-a', 'opt-a', 'opt-x', 'opt-y']), new DateTimeImmutable('2026-08-12 10:01:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(true, true));

    expect($result->score())->toBe(0)
        ->and($result->questionBreakdown()[0]->score())->toBe(0)
        ->and($result->questionBreakdown()[0]->percentage())->toBe(0)
        ->and($result->questionBreakdown()[0]->isCorrect())->toBeFalse();
});

it('mantiene todo o nada en multi_select cuando allowPartialCredit es false', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92254',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92255',
            '01981a64-8300-7b1d-b442-764ea7f92256',
            12,
            QuestionType::MultiSelect,
            MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b', 'opt-c']]),
        ),
    ]);

    $attempt->answer(1, MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]), new DateTimeImmutable('2026-08-12 10:01:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(false, true));

    expect($result->score())->toBe(0)
        ->and($result->questionBreakdown()[0]->score())->toBe(0)
        ->and($result->questionBreakdown()[0]->percentage())->toBe(0)
        ->and($result->questionBreakdown()[0]->isCorrect())->toBeFalse();
});

it('mantiene true_false como todo o nada aunque allowPartialCredit y applyPenalties esten activos', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92263',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92264',
            '01981a64-8300-7b1d-b442-764ea7f92265',
            6,
            QuestionType::TrueFalse,
            TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        ),
    ]);

    $attempt->answer(1, TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => false]), new DateTimeImmutable('2026-08-12 10:01:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(true, true));

    expect($result->score())->toBe(0)
        ->and($result->totalPoints())->toBe(6)
        ->and($result->percentage())->toBe(0)
        ->and($result->passed())->toBeFalse()
        ->and($result->questionBreakdown()[0]->score())->toBe(0)
        ->and($result->questionBreakdown()[0]->percentage())->toBe(0)
        ->and($result->questionBreakdown()[0]->isCorrect())->toBeFalse()
        ->and($result->questionBreakdown()[0]->isAnswered())->toBeTrue();
});

it('otorga partial credit en matching por pares correctos', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92257',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92258',
            '01981a64-8300-7b1d-b442-764ea7f92259',
            9,
            QuestionType::Matching,
            MatchingResponse::fromArray([
                'type' => 'matching',
                'pairs' => [
                    ['leftId' => 'l1', 'rightId' => 'r1'],
                    ['leftId' => 'l2', 'rightId' => 'r2'],
                    ['leftId' => 'l3', 'rightId' => 'r3'],
                ],
            ]),
        ),
    ]);

    $attempt->answer(1, MatchingResponse::fromArray([
        'type' => 'matching',
        'pairs' => [
            ['leftId' => 'l1', 'rightId' => 'r1'],
            ['leftId' => 'l2', 'rightId' => 'r9'],
            ['leftId' => 'l3', 'rightId' => 'r3'],
        ],
    ]), new DateTimeImmutable('2026-08-12 10:01:00'));

    $grader = new ExamAttemptGrader;

    $partial = $grader->grade($attempt, new GradingPolicy(true, false));
    $allOrNothing = $grader->grade($attempt, new GradingPolicy(false, false));

    expect($partial->score())->toBe(6)
        ->and($partial->questionBreakdown()[0]->score())->toBe(6)
        ->and($partial->questionBreakdown()[0]->percentage())->toBe(67)
        ->and($partial->questionBreakdown()[0]->isCorrect())->toBeFalse()
        ->and($allOrNothing->score())->toBe(0)
        ->and($allOrNothing->questionBreakdown()[0]->score())->toBe(0);
});

it('no sobrecuenta pares repetidos en matching', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92269',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92270',
            '01981a64-8300-7b1d-b442-764ea7f92271',
            9,
            QuestionType::Matching,
            MatchingResponse::fromArray([
                'type' => 'matching',
                'pairs' => [
                    ['leftId' => 'l1', 'rightId' => 'r1'],
                    ['leftId' => 'l2', 'rightId' => 'r2'],
                    ['leftId' => 'l3', 'rightId' => 'r3'],
                ],
            ]),
        ),
    ]);

    $attempt->answer(1, new MatchingResponse([
        ['leftId' => 'l1', 'rightId' => 'r1'],
        ['leftId' => 'l1', 'rightId' => 'r1'],
        ['leftId' => 'l2', 'rightId' => 'r9'],
    ]), new DateTimeImmutable('2026-08-12 10:01:00'));

    $result = (new ExamAttemptGrader)->grade($attempt, new GradingPolicy(true, false));

    expect($result->score())->toBe(3)
        ->and($result->questionBreakdown()[0]->score())->toBe(3)
        ->and($result->questionBreakdown()[0]->percentage())->toBe(33)
        ->and($result->questionBreakdown()[0]->isCorrect())->toBeFalse();
});

it('otorga partial credit en ordering por posicion correcta', function (): void {
    $attempt = examAttemptGraderAttempt([
        examAttemptGraderQuestion(
            '01981a64-8300-7b1d-b442-764ea7f92260',
            1,
            '01981a64-8300-7b1d-b442-764ea7f92261',
            '01981a64-8300-7b1d-b442-764ea7f92262',
            8,
            QuestionType::Ordering,
            OrderingResponse::fromArray([
                'type' => 'ordering',
                'itemIds' => ['i1', 'i2', 'i3', 'i4'],
            ]),
        ),
    ]);

    $attempt->answer(1, OrderingResponse::fromArray([
        'type' => 'ordering',
        'itemIds' => ['i1', 'i3', 'i2', 'i4'],
    ]), new DateTimeImmutable('2026-08-12 10:01:00'));

    $grader = new ExamAttemptGrader;

    $partial = $grader->grade($attempt, new GradingPolicy(true, false));
    $allOrNothing = $grader->grade($attempt, new GradingPolicy(false, false));

    expect($partial->score())->toBe(4)
        ->and($partial->questionBreakdown()[0]->score())->toBe(4)
        ->and($partial->questionBreakdown()[0]->percentage())->toBe(50)
        ->and($partial->questionBreakdown()[0]->isCorrect())->toBeFalse()
        ->and($allOrNothing->score())->toBe(0)
        ->and($allOrNothing->questionBreakdown()[0]->score())->toBe(0);
});
