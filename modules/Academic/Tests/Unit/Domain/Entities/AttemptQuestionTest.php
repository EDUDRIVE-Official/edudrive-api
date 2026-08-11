<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

it('construye una pregunta del intento con snapshot', function (): void {
    $attemptQuestion = AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        10,
        '¿Pregunta?',
        QuestionType::SingleChoice,
        [['refId' => 'opt-a', 'id' => '01981a64-8300-7b1d-b442-764ea7f92103', 'label' => 'A', 'position' => 1, 'side' => null]],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        'Explicación',
    );

    expect($attemptQuestion->position())->toBe(1)
        ->and($attemptQuestion->points())->toBe(10)
        ->and($attemptQuestion->type())->toBe(QuestionType::SingleChoice)
        ->and($attemptQuestion->correctResponse())->toBeInstanceOf(SingleChoiceResponse::class)
        ->and($attemptQuestion->userResponse())->toBeNull()
        ->and($attemptQuestion->isCorrect())->toBeNull();
});

it('rechaza posiciones y puntos inválidos en una pregunta del intento', function (): void {
    expect(fn () => AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        0,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        1,
        'P',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);

    expect(fn () => AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        0,
        'P',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);
});
