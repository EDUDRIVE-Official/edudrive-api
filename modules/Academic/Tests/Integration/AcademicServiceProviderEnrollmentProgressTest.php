<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\UseCases\CompleteLessonHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentCurriculumStatusHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentProgressHandler;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentProgressRepository;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

it('registra el repositorio de progreso de enrollment en el contenedor', function (): void {
    expect(app(EnrollmentProgressRepository::class))->toBeInstanceOf(EloquentEnrollmentProgressRepository::class);
});

it('registra handlers CQRS de progreso de enrollment en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(CompleteLessonCommand::class))->toBe(CompleteLessonHandler::class)
        ->and($registry->handlerFor(GetEnrollmentProgressQuery::class))->toBe(GetEnrollmentProgressHandler::class);
});

it('registra el handler de estado de curriculo en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(GetEnrollmentCurriculumStatusQuery::class))->toBe(GetEnrollmentCurriculumStatusHandler::class);
});
