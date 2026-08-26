<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\RoadPassport\Application\Commands\ChangeRoadPassportLevelCommand;
use Modules\RoadPassport\Application\Commands\IssueRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\ReactivateRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\RevokeRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\SuspendRoadPassportCommand;
use Modules\RoadPassport\Application\Queries\GetMyRoadPassportQuery;
use Modules\RoadPassport\Application\Queries\GetRoadPassportQuery;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Application\UseCases\ChangeRoadPassportLevelHandler;
use Modules\RoadPassport\Application\UseCases\GetMyRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\GetRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\IssueRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\ReactivateRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\RevokeRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\SuspendRoadPassportHandler;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoadPassportRepository;
use Modules\RoadPassport\Infrastructure\Services\DefaultRoadPassportEvidenceRecorder;

it('registra el repositorio de pasaporte vial en el contenedor', function (): void {
    expect(app(RoadPassportRepository::class))->toBeInstanceOf(EloquentRoadPassportRepository::class);
});

it('registra el recorder de evidencia de pasaporte vial en el contenedor', function (): void {
    expect(app(RoadPassportEvidenceRecorder::class))->toBeInstanceOf(DefaultRoadPassportEvidenceRecorder::class);
});

it('registra los handlers CQRS de pasaporte vial en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(IssueRoadPassportCommand::class))->toBe(IssueRoadPassportHandler::class)
        ->and($registry->handlerFor(SuspendRoadPassportCommand::class))->toBe(SuspendRoadPassportHandler::class)
        ->and($registry->handlerFor(ReactivateRoadPassportCommand::class))->toBe(ReactivateRoadPassportHandler::class)
        ->and($registry->handlerFor(RevokeRoadPassportCommand::class))->toBe(RevokeRoadPassportHandler::class)
        ->and($registry->handlerFor(ChangeRoadPassportLevelCommand::class))->toBe(ChangeRoadPassportLevelHandler::class)
        ->and($registry->handlerFor(GetRoadPassportQuery::class))->toBe(GetRoadPassportHandler::class)
        ->and($registry->handlerFor(GetMyRoadPassportQuery::class))->toBe(GetMyRoadPassportHandler::class);
});
