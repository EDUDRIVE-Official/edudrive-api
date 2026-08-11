<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\CreateQuestionCommand;
use Modules\Academic\Application\Commands\DeleteQuestionCommand;
use Modules\Academic\Application\Commands\UpdateQuestionCommand;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Queries\GetQuestionQuery;
use Modules\Academic\Application\Queries\ListQuestionsQuery;
use Modules\Academic\Application\Responses\QuestionListItemResponse;
use Modules\Academic\Application\Responses\QuestionResponse;
use Modules\Academic\Application\UseCases\CreateQuestionHandler;
use Modules\Academic\Application\UseCases\DeleteQuestionHandler;
use Modules\Academic\Application\UseCases\GetQuestionHandler;
use Modules\Academic\Application\UseCases\ListQuestionsHandler;
use Modules\Academic\Application\UseCases\UpdateQuestionHandler;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\Exceptions\InvalidQuestionScore;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final class InMemoryQuestionRepository implements QuestionRepository
{
    /** @var array<string, Question> */
    public array $questions = [];
    public int $saveCalls = 0;

    public function save(Question $question): void
    {
        $this->saveCalls++;
        $this->questions[$question->id()->value()] = $question;
    }

    public function findById(QuestionId $id): ?Question
    {
        return $this->questions[$id->value()] ?? null;
    }

    /** @return list<Question> */
    public function all(?CompetencyId $competencyId = null): array
    {
        return array_values(array_filter(
            $this->questions,
            static fn (Question $question): bool => $competencyId === null || $question->competencyId()->equals($competencyId),
        ));
    }

    public function delete(QuestionId $id): void
    {
        unset($this->questions[$id->value()]);
    }
}

function createCompetencyIdForQuestion(): string
{
    return persistedQuestionCompetencyId();
}

it('crea una pregunta de seleccion unica exitosamente', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competencyId = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    $response = $handler->handle(new CreateQuestionCommand(
        competencyId: $competencyId,
        type: 'single_choice',
        prompt: '¿Cuál es la velocidad máxima en ciudad?',
        score: 1,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => '50 km/h'],
            ['refId' => 'opt-b', 'label' => '80 km/h'],
        ],
    ));

    expect($response)->toBeInstanceOf(QuestionResponse::class)
        ->and($repository->saveCalls)->toBe(1)
        ->and($response->type)->toBe('single_choice')
        ->and($response->correct)->toBe(['type' => 'single_choice', 'optionId' => 'opt-a']);
});

it('rechaza crear una pregunta con competencia inexistente', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    expect(fn () => $handler->handle(new CreateQuestionCommand(
        competencyId: (string) Illuminate\Support\Str::uuid(),
        type: 'single_choice',
        prompt: 'Prompt',
        score: 1,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    )))->toThrow(QuestionNotFound::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('rechaza crear una pregunta con puntaje invalido sin guardar', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competencyId = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    expect(fn () => $handler->handle(new CreateQuestionCommand(
        competencyId: $competencyId,
        type: 'single_choice',
        prompt: 'Prompt',
        score: 0,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    )))->toThrow(InvalidQuestionScore::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('propaga errores de dominio del agregado sin guardar', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competencyId = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    expect(fn () => $handler->handle(new CreateQuestionCommand(
        competencyId: $competencyId,
        type: 'single_choice',
        prompt: 'Prompt',
        score: 1,
        response: ['optionId' => 'opt-x'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    )))->toThrow(InvalidQuestion::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('actualiza una pregunta existente', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competencyId = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    $created = $handler->handle(new CreateQuestionCommand(
        competencyId: $competencyId,
        type: 'single_choice',
        prompt: 'Prompt inicial',
        score: 1,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    ));

    $updated = (new UpdateQuestionHandler($repository))->handle(new UpdateQuestionCommand(
        questionId: $created->id,
        type: 'multi_select',
        prompt: 'Prompt cambiado',
        score: 3,
        response: ['optionIds' => ['opt-a', 'opt-b']],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
        explanation: 'Explicación nueva',
    ));

    expect($repository->saveCalls)->toBe(2)
        ->and($updated->prompt)->toBe('Prompt cambiado')
        ->and($updated->score)->toBe(3)
        ->and($updated->type)->toBe('multi_select')
        ->and($updated->correct)->toBe(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]);
});

it('rechaza actualizar una pregunta inexistente', function (): void {
    $repository = new InMemoryQuestionRepository;
    $handler = new UpdateQuestionHandler($repository);

    expect(fn () => $handler->handle(new UpdateQuestionCommand(
        questionId: (string) Illuminate\Support\Str::uuid(),
        type: 'single_choice',
        prompt: 'Prompt',
        score: 1,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    )))->toThrow(QuestionNotFound::class);
});

it('elimina una pregunta existente', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competencyId = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    $created = $handler->handle(new CreateQuestionCommand(
        competencyId: $competencyId,
        type: 'single_choice',
        prompt: 'Prompt',
        score: 1,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    ));

    $deleteHandler = new DeleteQuestionHandler($repository);
    $deleteHandler->handle(new DeleteQuestionCommand(questionId: $created->id));

    expect($repository->findById(QuestionId::fromString($created->id)))->toBeNull();
});

it('rechaza eliminar una pregunta inexistente', function (): void {
    $repository = new InMemoryQuestionRepository;
    $handler = new DeleteQuestionHandler($repository);

    expect(fn () => $handler->handle(new DeleteQuestionCommand(
        questionId: (string) Illuminate\Support\Str::uuid(),
    )))->toThrow(QuestionNotFound::class);
});

it('lista preguntas con y sin filtro por competencia', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $firstCompetency = persistedQuestionCompetencyId();
    $secondCompetency = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    $handler->handle(new CreateQuestionCommand(
        competencyId: $firstCompetency,
        type: 'single_choice',
        prompt: 'Primera',
        score: 1,
        response: ['optionId' => 'opt-a'],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    ));
    $handler->handle(new CreateQuestionCommand(
        competencyId: $secondCompetency,
        type: 'true_false',
        prompt: 'Segunda',
        score: 1,
        response: ['correct' => true],
    ));

    $all = (new ListQuestionsHandler($repository))->handle(new ListQuestionsQuery);
    expect($all)->toHaveCount(2)
        ->and($all[0])->toBeInstanceOf(QuestionListItemResponse::class);

    $filtered = (new ListQuestionsHandler($repository))->handle(new ListQuestionsQuery($firstCompetency));
    expect($filtered)->toHaveCount(1)
        ->and($filtered[0]->competencyId)->toBe($firstCompetency);
});

it('obtiene el detalle de una pregunta', function (): void {
    $relations = app(\Modules\Academic\Domain\Repositories\CompetencyRepository::class);
    $competencyId = persistedQuestionCompetencyId();
    $repository = new InMemoryQuestionRepository;
    $handler = new CreateQuestionHandler($repository, $relations);

    $created = $handler->handle(new CreateQuestionCommand(
        competencyId: $competencyId,
        type: 'multi_select',
        prompt: 'Prompt',
        score: 2,
        response: ['optionIds' => ['opt-a', 'opt-b']],
        options: [
            ['refId' => 'opt-a', 'label' => 'A'],
            ['refId' => 'opt-b', 'label' => 'B'],
        ],
    ));

    $detail = (new GetQuestionHandler($repository))->handle(new GetQuestionQuery($created->id));

    expect($detail)->toBeInstanceOf(QuestionResponse::class)
        ->and($detail->correct)->toBe(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]);
});

it('rechaza obtener el detalle de una pregunta inexistente', function (): void {
    $repository = new InMemoryQuestionRepository;
    $handler = new GetQuestionHandler($repository);

    expect(fn () => $handler->handle(new GetQuestionQuery((string) Illuminate\Support\Str::uuid())))
        ->toThrow(QuestionNotFound::class);
});