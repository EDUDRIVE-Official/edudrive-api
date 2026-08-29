<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\Enums\AiPromptStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiPromptTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

function newAiPrompt(): AiPrompt
{
    return AiPrompt::create(
        id: AiPromptId::fromString((string) Str::uuid()),
        identifier: 'tutor.saludo',
        purpose: 'Saludar al estudiante',
        modelId: (string) Str::uuid(),
        authorId: (string) Str::uuid(),
        content: 'Eres un tutor amable.',
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se crea en version uno y estado borrador', function (): void {
    $prompt = newAiPrompt();

    expect($prompt->version())->toBe(1)
        ->and($prompt->status())->toBe(AiPromptStatus::Draft);
});

it('incrementa la version al actualizar el contenido', function (): void {
    $prompt = newAiPrompt();

    $prompt->updateContent('Eres un tutor amable y paciente.');

    expect($prompt->version())->toBe(2)
        ->and($prompt->content())->toBe('Eres un tutor amable y paciente.');
});

it('aprueba y retira un prompt', function (): void {
    $prompt = newAiPrompt();

    $prompt->approve();
    expect($prompt->status())->toBe(AiPromptStatus::Approved);

    $prompt->retire();
    expect($prompt->status())->toBe(AiPromptStatus::Retired);
});

it('rechaza actualizar el contenido de un prompt retirado', function (): void {
    $prompt = newAiPrompt();
    $prompt->approve();
    $prompt->retire();

    expect(fn () => $prompt->updateContent('nuevo contenido'))->toThrow(InvalidAiPromptTransition::class);
});

it('rechaza aprobar un prompt que no esta en borrador', function (): void {
    $prompt = newAiPrompt();
    $prompt->approve();

    expect(fn () => $prompt->approve())->toThrow(InvalidAiPromptTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = AiPromptId::fromString((string) Str::uuid());
    $createdAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');

    $prompt = AiPrompt::restore(
        id: $id,
        identifier: 'tutor.despedida',
        purpose: 'Despedir al estudiante',
        modelId: 'model-1',
        version: 3,
        authorId: 'author-1',
        content: 'Hasta pronto.',
        status: AiPromptStatus::Approved,
        createdAt: $createdAt,
    );

    expect($prompt->id()->equals($id))->toBeTrue()
        ->and($prompt->identifier())->toBe('tutor.despedida')
        ->and($prompt->version())->toBe(3)
        ->and($prompt->status())->toBe(AiPromptStatus::Approved)
        ->and($prompt->createdAt())->toBe($createdAt);
});
