<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ApproveAiModelCommand;
use Modules\AiGovernance\Application\Commands\DeprecateAiModelCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiModelCommand;
use Modules\AiGovernance\Application\Commands\RetireAiModelCommand;
use Modules\AiGovernance\Application\Exceptions\AiModelNotFound;
use Modules\AiGovernance\Application\Queries\GetAiModelQuery;
use Modules\AiGovernance\Application\Queries\ListAiModelsQuery;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Application\UseCases\ApproveAiModelHandler;
use Modules\AiGovernance\Application\UseCases\DeprecateAiModelHandler;
use Modules\AiGovernance\Application\UseCases\GetAiModelHandler;
use Modules\AiGovernance\Application\UseCases\ListAiModelsHandler;
use Modules\AiGovernance\Application\UseCases\RegisterAiModelHandler;
use Modules\AiGovernance\Application\UseCases\RetireAiModelHandler;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiModelTransition;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

final class InMemoryAiModelRepository implements AiModelRepository
{
    /** @var array<string, AiModel> */
    public array $items = [];

    public function save(AiModel $model): void
    {
        $this->items[$model->id()->value()] = $model;
    }

    public function findById(AiModelId $id): ?AiModel
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AiModel> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

function persistedAiModelFor(InMemoryAiModelRepository $repository): AiModel
{
    $model = AiModel::register(
        id: AiModelId::fromString((string) Str::uuid()),
        name: 'Modelo de recomendacion',
        provider: 'interno',
        version: '1.0.0',
        ownerId: null,
        useCase: null,
    );
    $repository->save($model);

    return $model;
}

it('registra un modelo de IA nuevo', function (): void {
    $repository = new InMemoryAiModelRepository;

    $response = (new RegisterAiModelHandler($repository))->handle(new RegisterAiModelCommand(
        name: 'GPT institucional',
        provider: 'openai',
        version: '2024-05',
        ownerId: (string) Str::uuid(),
        useCase: 'asistente',
        knownRisks: 'alucinaciones',
    ));

    expect($response)->toBeInstanceOf(AiModelResponse::class)
        ->and($response->name)->toBe('GPT institucional')
        ->and($response->status)->toBe('registered');
});

it('aprueba, deprecia y retira un modelo existente', function (): void {
    $repository = new InMemoryAiModelRepository;
    $model = persistedAiModelFor($repository);

    $approved = (new ApproveAiModelHandler($repository))->handle(new ApproveAiModelCommand($model->id()->value()));
    expect($approved->status)->toBe('approved');

    $deprecated = (new DeprecateAiModelHandler($repository))->handle(new DeprecateAiModelCommand($model->id()->value()));
    expect($deprecated->status)->toBe('deprecated');

    $retired = (new RetireAiModelHandler($repository))->handle(new RetireAiModelCommand($model->id()->value()));
    expect($retired->status)->toBe('retired');
});

it('rechaza mutar un modelo inexistente', function (): void {
    $repository = new InMemoryAiModelRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new ApproveAiModelHandler($repository))->handle(new ApproveAiModelCommand($id)))
        ->toThrow(AiModelNotFound::class);
});

it('propaga el rechazo de dominio ante una transicion invalida', function (): void {
    $repository = new InMemoryAiModelRepository;
    $model = persistedAiModelFor($repository);

    expect(fn () => (new DeprecateAiModelHandler($repository))->handle(new DeprecateAiModelCommand($model->id()->value())))
        ->toThrow(InvalidAiModelTransition::class);
});

it('consulta y lista modelos de IA', function (): void {
    $repository = new InMemoryAiModelRepository;
    $model = persistedAiModelFor($repository);
    persistedAiModelFor($repository);

    $found = (new GetAiModelHandler($repository))->handle(new GetAiModelQuery($model->id()->value()));
    expect($found->id)->toBe($model->id()->value());

    $listed = (new ListAiModelsHandler($repository))->handle(new ListAiModelsQuery);
    expect($listed)->toHaveCount(2);
});
