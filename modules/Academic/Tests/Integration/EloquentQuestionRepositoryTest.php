<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\QuestionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\QuestionOptionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentQuestionRepository;

/** @return list<QuestionOption> */
function questionRepoOptions(array $refIds, array $sides = []): array
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

it('guarda y reconstruye una pregunta de seleccion unica', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        '¿Cuál es el límite en zona escolar?',
        2,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionRepoOptions(['opt-a', 'opt-b']),
        'La zona escolar exige reducir la velocidad.',
    );

    $repository->save($question);

    $stored = $repository->findById($question->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->type())->toBe(QuestionType::SingleChoice)
        ->and($stored?->competencyId()->equals($competencyId))->toBeTrue()
        ->and($stored?->score())->toBe(2)
        ->and($stored?->explanation())->toBe('La zona escolar exige reducir la velocidad.')
        ->and($stored?->response()->toArray())->toBe(['type' => 'single_choice', 'optionId' => 'opt-a'])
        ->and($stored?->options())->toHaveCount(2)
        ->and($stored?->options()[0]->refId())->toBe('opt-a')
        ->and($stored?->options()[0]->side())->toBeNull();
});

it('guarda y reconstruye un emparejamiento con lados en las opciones', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::Matching,
        $competencyId,
        'Asocia cada señal con su significado.',
        1,
        MatchingResponse::fromArray(['type' => 'matching', 'pairs' => [
            ['leftId' => 'left-1', 'rightId' => 'right-1'],
            ['leftId' => 'left-2', 'rightId' => 'right-2'],
        ]]),
        questionRepoOptions(['left-1', 'left-2', 'right-1', 'right-2'], ['left', 'left', 'right', 'right']),
    );

    $repository->save($question);

    $stored = $repository->findById($question->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->type())->toBe(QuestionType::Matching)
        ->and($stored?->response()->toArray())->toBe(['type' => 'matching', 'pairs' => [
            ['leftId' => 'left-1', 'rightId' => 'right-1'],
            ['leftId' => 'left-2', 'rightId' => 'right-2'],
        ]])
        ->and($stored?->options()[0]->side())->toBe('left')
        ->and($stored?->options()[2]->side())->toBe('right');
});

it('lista preguntas ordenadas y filtra por competencia', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $firstCompetency = CompetencyId::fromString(persistedQuestionCompetencyId());
    $secondCompetency = CompetencyId::fromString(persistedQuestionCompetencyId());

    $repository->save(Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::TrueFalse,
        $firstCompetency,
        'Primera',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    ));
    $repository->save(Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::MultiSelect,
        $secondCompetency,
        'Segunda',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        questionRepoOptions(['opt-a', 'opt-b']),
    ));

    expect($repository->all())->toHaveCount(2);
    $filtered = $repository->all($firstCompetency);
    expect($filtered)->toHaveCount(1)
        ->and($filtered[0]->competencyId()->equals($firstCompetency))->toBeTrue();
});

it('elimina en cascada las opciones al borrar la pregunta', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionRepoOptions(['opt-a', 'opt-b']),
    );
    $repository->save($question);

    $repository->delete($question->id());

    expect($repository->findById($question->id()))->toBeNull()
        ->and(QuestionModel::query()->find($question->id()->value()))->toBeNull()
        ->and(QuestionOptionModel::query()
            ->where('question_id', $question->id()->value())->count())->toBe(0);
});

it('lanza un QueryException al guardar una pregunta con competencia inexistente', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::TrueFalse,
        CompetencyId::fromString((string) Str::uuid()),
        'Prompt',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    );

    expect(fn () => $repository->save($question))->toThrow(QueryException::class);
});

it('carga preguntas con opciones sin consultas N+1', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    foreach (['a', 'b', 'c'] as $i) {
        $repository->save(Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            "Pregunta {$i}",
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
            questionRepoOptions(['opt-a', 'opt-b']),
        ));
    }

    DB::enableQueryLog();
    $questions = $repository->all();
    $rawQueries = collect(DB::getQueryLog())->filter(
        static fn (array $query): bool => ! str_starts_with((string) $query['query'], 'select * from "sqlite_master"')
            && ! str_starts_with((string) $query['query'], 'PRAGMA')
            && ! str_starts_with((string) $query['query'], 'select * from (select (select')
    )->values();

    expect($questions)->toHaveCount(3);
    expect($rawQueries->count())->toBeLessThanOrEqual(2);
});

it('revierte el response corrupto persistido con InvalidQuestion', function (): void {
    $repository = app(EloquentQuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        'Prompt',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        questionRepoOptions(['opt-a', 'opt-b']),
    );
    $repository->save($question);

    QuestionModel::query()->where('id', $question->id()->value())->update([
        'response' => json_encode(['type' => 'single_choice', 'optionId' => 123], JSON_THROW_ON_ERROR),
    ]);

    expect(fn () => $repository->findById($question->id()))->toThrow(InvalidQuestion::class);
});
