<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Enums\AiModelStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiModelTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

function newAiModel(): AiModel
{
    return AiModel::register(
        id: AiModelId::fromString((string) Str::uuid()),
        name: 'Modelo de recomendacion',
        provider: 'interno',
        version: '1.0.0',
        ownerId: (string) Str::uuid(),
        useCase: 'Recomendar rutas de aprendizaje',
        registeredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se registra en estado registered', function (): void {
    $model = newAiModel();

    expect($model->status())->toBe(AiModelStatus::Registered);
});

it('avanza de registered a approved a deprecated a retired', function (): void {
    $model = newAiModel();

    $model->approve();
    expect($model->status())->toBe(AiModelStatus::Approved);

    $model->deprecate();
    expect($model->status())->toBe(AiModelStatus::Deprecated);

    $model->retire();
    expect($model->status())->toBe(AiModelStatus::Retired);
});

it('rechaza transiciones invalidas', function (): void {
    $model = newAiModel();

    expect(fn () => $model->deprecate())->toThrow(InvalidAiModelTransition::class);

    $model->approve();
    expect(fn () => $model->approve())->toThrow(InvalidAiModelTransition::class);
});

it('rechaza retirar un modelo ya retirado', function (): void {
    $model = newAiModel();
    $model->approve();
    $model->deprecate();
    $model->retire();

    expect(fn () => $model->retire())->toThrow(InvalidAiModelTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = AiModelId::fromString((string) Str::uuid());
    $registeredAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');

    $model = AiModel::restore(
        id: $id,
        name: 'GPT institucional',
        provider: 'openai',
        version: '2024-05',
        ownerId: 'owner-1',
        useCase: 'asistente',
        status: AiModelStatus::Approved,
        knownRisks: 'alucinaciones',
        registeredAt: $registeredAt,
    );

    expect($model->id()->equals($id))->toBeTrue()
        ->and($model->name())->toBe('GPT institucional')
        ->and($model->status())->toBe(AiModelStatus::Approved)
        ->and($model->knownRisks())->toBe('alucinaciones')
        ->and($model->registeredAt())->toBe($registeredAt);
});
