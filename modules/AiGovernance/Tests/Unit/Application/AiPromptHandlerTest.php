<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ApproveAiPromptCommand;
use Modules\AiGovernance\Application\Commands\CreateAiPromptCommand;
use Modules\AiGovernance\Application\Commands\RetireAiPromptCommand;
use Modules\AiGovernance\Application\Commands\UpdateAiPromptContentCommand;
use Modules\AiGovernance\Application\Exceptions\AiPromptNotFound;
use Modules\AiGovernance\Application\Queries\GetAiPromptQuery;
use Modules\AiGovernance\Application\Queries\ListAiPromptsQuery;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Application\UseCases\ApproveAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\CreateAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\GetAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\ListAiPromptsHandler;
use Modules\AiGovernance\Application\UseCases\RetireAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\UpdateAiPromptContentHandler;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiPromptTransition;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

final class InMemoryAiPromptRepository implements AiPromptRepository
{
    /** @var array<string, AiPrompt> */
    public array $items = [];

    public function save(AiPrompt $prompt): void
    {
        $this->items[$prompt->id()->value()] = $prompt;
    }

    public function findById(AiPromptId $id): ?AiPrompt
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AiPrompt> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

function persistedAiPromptFor(InMemoryAiPromptRepository $repository): AiPrompt
{
    $prompt = AiPrompt::create(
        id: AiPromptId::fromString((string) Str::uuid()),
        identifier: 'saludo-inicial',
        purpose: 'saludar al estudiante',
        modelId: null,
        authorId: null,
        content: 'Hola, {{nombre}}',
    );
    $repository->save($prompt);

    return $prompt;
}

it('crea un prompt de IA nuevo', function (): void {
    $repository = new InMemoryAiPromptRepository;

    $response = (new CreateAiPromptHandler($repository))->handle(new CreateAiPromptCommand(
        identifier: 'resumen-curso',
        purpose: 'resumir contenido del curso',
        modelId: null,
        authorId: (string) Str::uuid(),
        content: 'Resume el curso {{curso}}',
    ));

    expect($response)->toBeInstanceOf(AiPromptResponse::class)
        ->and($response->version)->toBe(1)
        ->and($response->status)->toBe('draft');
});

it('actualiza contenido, aprueba y retira un prompt existente', function (): void {
    $repository = new InMemoryAiPromptRepository;
    $prompt = persistedAiPromptFor($repository);

    $updated = (new UpdateAiPromptContentHandler($repository))->handle(new UpdateAiPromptContentCommand($prompt->id()->value(), 'Hola de nuevo, {{nombre}}'));
    expect($updated->version)->toBe(2);

    $approved = (new ApproveAiPromptHandler($repository))->handle(new ApproveAiPromptCommand($prompt->id()->value()));
    expect($approved->status)->toBe('approved');

    $retired = (new RetireAiPromptHandler($repository))->handle(new RetireAiPromptCommand($prompt->id()->value()));
    expect($retired->status)->toBe('retired');
});

it('rechaza mutar un prompt inexistente', function (): void {
    $repository = new InMemoryAiPromptRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new ApproveAiPromptHandler($repository))->handle(new ApproveAiPromptCommand($id)))
        ->toThrow(AiPromptNotFound::class);
});

it('propaga el rechazo de dominio al actualizar un prompt retirado', function (): void {
    $repository = new InMemoryAiPromptRepository;
    $prompt = persistedAiPromptFor($repository);
    (new RetireAiPromptHandler($repository))->handle(new RetireAiPromptCommand($prompt->id()->value()));

    expect(fn () => (new UpdateAiPromptContentHandler($repository))->handle(new UpdateAiPromptContentCommand($prompt->id()->value(), 'otro contenido')))
        ->toThrow(InvalidAiPromptTransition::class);
});

it('consulta y lista prompts de IA', function (): void {
    $repository = new InMemoryAiPromptRepository;
    $prompt = persistedAiPromptFor($repository);
    persistedAiPromptFor($repository);

    $found = (new GetAiPromptHandler($repository))->handle(new GetAiPromptQuery($prompt->id()->value()));
    expect($found->id)->toBe($prompt->id()->value());

    $listed = (new ListAiPromptsHandler($repository))->handle(new ListAiPromptsQuery);
    expect($listed)->toHaveCount(2);
});
