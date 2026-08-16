<?php

declare(strict_types=1);

use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Infrastructure\Persistence\Eloquent\Repositories\EloquentLearningEventRepository;
use Modules\Learning\Infrastructure\Services\DefaultLearningEventRecorder;

it('registra el repositorio de eventos de aprendizaje en el contenedor', function (): void {
    expect(app(LearningEventRepository::class))->toBeInstanceOf(EloquentLearningEventRepository::class);
});

it('registra el recorder de eventos de aprendizaje en el contenedor', function (): void {
    expect(app(LearningEventRecorder::class))->toBeInstanceOf(DefaultLearningEventRecorder::class);
});
