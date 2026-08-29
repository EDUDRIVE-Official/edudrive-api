<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Enums\AiModelStatus;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

uses(RefreshDatabase::class);

function newPersistableAiModel(): AiModel
{
    return AiModel::register(
        id: AiModelId::fromString((string) Str::uuid()),
        name: 'Modelo de recomendacion',
        provider: 'interno',
        version: '1.0.0',
        ownerId: (string) Str::uuid(),
        useCase: 'Recomendar rutas de aprendizaje',
        knownRisks: 'ninguno conocido',
        registeredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('guarda y recupera un modelo de IA por identificador', function (): void {
    $model = newPersistableAiModel();

    app(AiModelRepository::class)->save($model);
    $found = app(AiModelRepository::class)->findById($model->id());

    expect($found)->not->toBeNull()
        ->and($found?->name())->toBe('Modelo de recomendacion')
        ->and($found?->status())->toBe(AiModelStatus::Registered)
        ->and($found?->knownRisks())->toBe('ninguno conocido');
});

it('guarda y recupera un modelo aprobado', function (): void {
    $model = newPersistableAiModel();
    $model->approve();

    app(AiModelRepository::class)->save($model);
    $found = app(AiModelRepository::class)->findById($model->id());

    expect($found?->status())->toBe(AiModelStatus::Approved);
});

it('lista todos los modelos registrados', function (): void {
    $repository = app(AiModelRepository::class);
    $repository->save(newPersistableAiModel());
    $repository->save(newPersistableAiModel());

    expect($repository->all())->toHaveCount(2);
});
