<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\Exceptions\InvalidQuestionScore;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionMedia;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

function questionId(): QuestionId
{
    return QuestionId::fromString((string) Str::uuid());
}

function competencyId(): CompetencyId
{
    return CompetencyId::fromString((string) Str::uuid());
}

/** @param list<string> $sides */
function questionOptions(array $refIds, array $sides = []): array
{
    $options = [];
    foreach ($refIds as $index => $refId) {
        $options[] = QuestionOption::create(
            $refId,
            QuestionOptionId::fromString((string) Str::uuid()),
            $index + 1,
            "Opcion {$refId}",
            $sides[$index] ?? null,
        );
    }

    return $options;
}

it('construye una pregunta de seleccion unica valida', function (): void {
    $id = questionId();
    $competency = competencyId();

    $question = Question::create(
        $id,
        QuestionType::SingleChoice,
        $competency,
        '¿Cuál es la velocidad máxima en zona urbana?',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
    );

    expect($question->id()->equals($id))->toBeTrue()
        ->and($question->type())->toBe(QuestionType::SingleChoice)
        ->and($question->competencyId()->equals($competency))->toBeTrue()
        ->and($question->prompt())->toBe('¿Cuál es la velocidad máxima en zona urbana?')
        ->and($question->score())->toBe(1)
        ->and($question->explanation())->toBeNull()
        ->and($question->media())->toBe([])
        ->and(count($question->options()))->toBe(2)
        ->and($question->response()->toArray())->toBe(['type' => 'single_choice', 'optionId' => 'opt-a']);
});

it('rechaza una pregunta con puntaje no positivo', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt',
        0,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
    ))->toThrow(InvalidQuestionScore::class);
});

it('rechaza una pregunta con guion vacio', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::TrueFalse,
        competencyId(),
        '   ',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza una respuesta cuya opcion no existe', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-x']),
        questionOptions(['opt-a', 'opt-b']),
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza opciones con posiciones no consecutivas', function (): void {
    $options = questionOptions(['opt-a', 'opt-b']);
    $options[1] = QuestionOption::create(
        'opt-b',
        QuestionOptionId::fromString((string) Str::uuid()),
        3,
        'Opcion opt-b',
    );

    expect(fn () => Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        $options,
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza refIds duplicados dentro de la pregunta', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::MultiSelect,
        competencyId(),
        'Prompt',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        questionOptions(['opt-a', 'opt-a']),
    ))->toThrow(InvalidQuestion::class);
});

it('acepta una pregunta verdadero o falso sin opciones', function (): void {
    $question = Question::create(
        questionId(),
        QuestionType::TrueFalse,
        competencyId(),
        '¿Un semáforo en rojo obliga a detenerse?',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    );

    expect($question->type())->toBe(QuestionType::TrueFalse)
        ->and($question->options())->toBe([]);
});

it('rechaza una pregunta de seleccion unica con menos de dos opciones', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a']),
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza un emparejamiento con lado izquierdo inexistente', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::Matching,
        competencyId(),
        'Prompt',
        1,
        MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
            ['leftId' => 'left-x', 'rightId' => 'right-1'],
            ['leftId' => 'left-2', 'rightId' => 'right-2'],
        ]]),
        questionOptions(['left-1', 'left-2', 'right-1', 'right-2'], ['left', 'left', 'right', 'right']),
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza un emparejamiento con lado derecho inexistente', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::Matching,
        competencyId(),
        'Prompt',
        1,
        MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
            ['leftId' => 'left-1', 'rightId' => 'right-x'],
            ['leftId' => 'left-2', 'rightId' => 'right-2'],
        ]]),
        questionOptions(['left-1', 'left-2', 'right-1', 'right-2'], ['left', 'left', 'right', 'right']),
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza un ordenamiento con item inexistente', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::Ordering,
        competencyId(),
        'Prompt',
        1,
        OrderingResponse::fromArray(['type' => 'ordering', 'itemIds' => ['a', 'x', 'c']]),
        questionOptions(['a', 'b', 'c']),
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza media con URL no HTTPS', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::Situational,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
        null,
        [QuestionMedia::fromArray(['type' => 'image', 'url' => 'http://example.com/imagen.jpg'])],
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza una pregunta situacional sin media', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::Situational,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
    ))->toThrow(InvalidQuestion::class);
});

it('rechaza media en una pregunta que no es situacional', function (): void {
    expect(fn () => Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
        null,
        [QuestionMedia::fromArray(['type' => 'image', 'url' => 'https://example.com/imagen.jpg'])],
    ))->toThrow(InvalidQuestion::class);
});

it('construye una pregunta situacional con media', function (): void {
    $question = Question::create(
        questionId(),
        QuestionType::Situational,
        competencyId(),
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
        null,
        [QuestionMedia::fromArray(['type' => 'image', 'url' => 'https://example.com/imagen.jpg'])],
    );

    expect($question->type())->toBe(QuestionType::Situational)
        ->and($question->media())->toHaveCount(1);
});

it('restaura el agregado con su estado completo', function (): void {
    $id = questionId();
    $competency = competencyId();

    $question = Question::restore(
        $id,
        QuestionType::MultiSelect,
        $competency,
        'Prompt',
        2,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        questionOptions(['opt-a', 'opt-b']),
        'Explicación de prueba',
    );

    expect($question->id()->equals($id))->toBeTrue()
        ->and($question->competencyId()->equals($competency))->toBeTrue()
        ->and($question->explanation())->toBe('Explicación de prueba')
        ->and($question->score())->toBe(2)
        ->and($question->response()->toArray())->toBe(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]);
});

it('reemplaza los datos de una pregunta de forma atomica', function (): void {
    $question = Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt inicial',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
    );

    $question->replace(
        QuestionType::MultiSelect,
        'Prompt cambiado',
        3,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-c']]),
        questionOptions(['opt-a', 'opt-b', 'opt-c']),
        'Nueva explicación',
    );

    expect($question->type())->toBe(QuestionType::MultiSelect)
        ->and($question->prompt())->toBe('Prompt cambiado')
        ->and($question->score())->toBe(3)
        ->and($question->explanation())->toBe('Nueva explicación')
        ->and(count($question->options()))->toBe(3)
        ->and($question->response()->toArray())->toBe(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-c']]);
});

it('rechaza un replace con datos invalidos sin mutar', function (): void {
    $question = Question::create(
        questionId(),
        QuestionType::SingleChoice,
        competencyId(),
        'Prompt inicial',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionOptions(['opt-a', 'opt-b']),
    );

    expect(fn () => $question->replace(
        QuestionType::SingleChoice,
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-z']),
        questionOptions(['opt-a', 'opt-b']),
    ))->toThrow(InvalidQuestion::class);

    expect($question->prompt())->toBe('Prompt inicial')
        ->and($question->response()->toArray())->toBe(['type' => 'single_choice', 'optionId' => 'opt-a']);
});
