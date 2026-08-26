<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

function attemptQuestionCompetencyId(): CompetencyId
{
    return CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92104');
}

it('construye una pregunta del intento con snapshot', function (): void {
    $attemptQuestion = AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        attemptQuestionCompetencyId(),
        10,
        '¿Pregunta?',
        QuestionType::SingleChoice,
        [['refId' => 'opt-a', 'id' => '01981a64-8300-7b1d-b442-764ea7f92103', 'label' => 'A', 'position' => 1, 'side' => null]],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        'Explicación',
    );

    expect($attemptQuestion->position())->toBe(1)
        ->and($attemptQuestion->points())->toBe(10)
        ->and($attemptQuestion->competencyId()->equals(attemptQuestionCompetencyId()))->toBeTrue()
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
        attemptQuestionCompetencyId(),
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
        attemptQuestionCompetencyId(),
        0,
        'P',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);
});

it('rechaza un prompt vacío o con solo espacios en una pregunta del intento', function (): void {
    expect(fn () => AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        attemptQuestionCompetencyId(),
        10,
        '',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);

    expect(fn () => AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        attemptQuestionCompetencyId(),
        10,
        '   ',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ))->toThrow(InvalidExamAttempt::class);
});

it('guarda el prompt recortado en una pregunta del intento', function (): void {
    $attemptQuestion = AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        attemptQuestionCompetencyId(),
        10,
        '  ¿Pregunta?  ',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    );

    expect($attemptQuestion->prompt())->toBe('¿Pregunta?');
});

it('restaura una pregunta del intento con los mismos accesores', function (): void {
    $attemptQuestion = AttemptQuestion::restore(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92101'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92102'),
        attemptQuestionCompetencyId(),
        10,
        '¿Pregunta?',
        QuestionType::SingleChoice,
        [['refId' => 'opt-a', 'id' => '01981a64-8300-7b1d-b442-764ea7f92103', 'label' => 'A', 'position' => 1, 'side' => null]],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    );

    expect($attemptQuestion->position())->toBe(1)
        ->and($attemptQuestion->points())->toBe(10)
        ->and($attemptQuestion->competencyId()->equals(attemptQuestionCompetencyId()))->toBeTrue()
        ->and($attemptQuestion->prompt())->toBe('¿Pregunta?')
        ->and($attemptQuestion->userResponse())->toBeNull();
});

it('expone competency id al crear y restaurar snapshots persistibles', function (): void {
    $competencyId = attemptQuestionCompetencyId();

    $created = AttemptQuestion::create(
        AttemptQuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92111'),
        1,
        QuestionId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
        $competencyId,
        5,
        '¿Con competencia?',
        QuestionType::SingleChoice,
        [['refId' => 'opt-a', 'id' => '01981a64-8300-7b1d-b442-764ea7f92113', 'label' => 'A', 'position' => 1, 'side' => null]],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    );

    $snapshot = [
        'id' => $created->id()->value(),
        'question_id' => $created->questionId()->value(),
        'competency_id' => $created->competencyId()->value(),
        'points' => $created->points(),
    ];

    $restored = AttemptQuestion::restore(
        AttemptQuestionId::fromString($snapshot['id']),
        1,
        QuestionId::fromString($snapshot['question_id']),
        CompetencyId::fromString($snapshot['competency_id']),
        $snapshot['points'],
        '¿Con competencia?',
        QuestionType::SingleChoice,
        [['refId' => 'opt-a', 'id' => '01981a64-8300-7b1d-b442-764ea7f92113', 'label' => 'A', 'position' => 1, 'side' => null]],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    );

    expect($snapshot['competency_id'])->toBe($competencyId->value())
        ->and($created->competencyId()->equals($competencyId))->toBeTrue()
        ->and($restored->competencyId()->equals($competencyId))->toBeTrue();
});
