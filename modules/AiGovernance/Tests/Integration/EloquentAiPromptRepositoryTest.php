<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\Enums\AiPromptStatus;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

uses(RefreshDatabase::class);

function newPersistableAiPrompt(): AiPrompt
{
    return AiPrompt::create(
        id: AiPromptId::fromString((string) Str::uuid()),
        identifier: 'tutor.saludo.'.strtolower((string) Str::random(6)),
        purpose: 'Saludar al estudiante',
        modelId: null,
        authorId: (string) Str::uuid(),
        content: 'Eres un tutor amable.',
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('guarda y recupera un prompt por identificador', function (): void {
    $prompt = newPersistableAiPrompt();

    app(AiPromptRepository::class)->save($prompt);
    $found = app(AiPromptRepository::class)->findById($prompt->id());

    expect($found)->not->toBeNull()
        ->and($found?->identifier())->toBe($prompt->identifier())
        ->and($found?->version())->toBe(1)
        ->and($found?->status())->toBe(AiPromptStatus::Draft);
});

it('guarda y recupera un prompt editado con su version incrementada', function (): void {
    $prompt = newPersistableAiPrompt();
    $prompt->updateContent('Contenido actualizado.');

    app(AiPromptRepository::class)->save($prompt);
    $found = app(AiPromptRepository::class)->findById($prompt->id());

    expect($found?->version())->toBe(2)
        ->and($found?->content())->toBe('Contenido actualizado.');
});

it('lista todos los prompts registrados', function (): void {
    $repository = app(AiPromptRepository::class);
    $repository->save(newPersistableAiPrompt());
    $repository->save(newPersistableAiPrompt());

    expect($repository->all())->toHaveCount(2);
});
