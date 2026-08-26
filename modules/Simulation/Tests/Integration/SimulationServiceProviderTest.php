<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Simulation\Application\Commands\CancelSimulationSessionCommand;
use Modules\Simulation\Application\Commands\CompleteSimulationSessionCommand;
use Modules\Simulation\Application\Commands\ReactivateSimulatorCommand;
use Modules\Simulation\Application\Commands\RegisterSimulatorCommand;
use Modules\Simulation\Application\Commands\RetireSimulatorCommand;
use Modules\Simulation\Application\Commands\RotateSimulatorIntegrationKeyCommand;
use Modules\Simulation\Application\Commands\ScheduleSimulationSessionCommand;
use Modules\Simulation\Application\Commands\StartSimulationSessionCommand;
use Modules\Simulation\Application\Commands\SubmitTelemetryCommand;
use Modules\Simulation\Application\Commands\SuspendSimulatorCommand;
use Modules\Simulation\Application\Queries\GetMySimulationSessionsQuery;
use Modules\Simulation\Application\Queries\GetPracticalResultQuery;
use Modules\Simulation\Application\Queries\GetSessionTelemetryQuery;
use Modules\Simulation\Application\Queries\GetSimulationSessionQuery;
use Modules\Simulation\Application\Queries\GetSimulatorQuery;
use Modules\Simulation\Application\Queries\ListSimulationSessionsQuery;
use Modules\Simulation\Application\Queries\ListSimulatorsQuery;
use Modules\Simulation\Application\UseCases\CancelSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\CompleteSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\GetMySimulationSessionsHandler;
use Modules\Simulation\Application\UseCases\GetPracticalResultHandler;
use Modules\Simulation\Application\UseCases\GetSessionTelemetryHandler;
use Modules\Simulation\Application\UseCases\GetSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\GetSimulatorHandler;
use Modules\Simulation\Application\UseCases\ListSimulationSessionsHandler;
use Modules\Simulation\Application\UseCases\ListSimulatorsHandler;
use Modules\Simulation\Application\UseCases\ReactivateSimulatorHandler;
use Modules\Simulation\Application\UseCases\RegisterSimulatorHandler;
use Modules\Simulation\Application\UseCases\RetireSimulatorHandler;
use Modules\Simulation\Application\UseCases\RotateSimulatorIntegrationKeyHandler;
use Modules\Simulation\Application\UseCases\ScheduleSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\StartSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\SubmitTelemetryHandler;
use Modules\Simulation\Application\UseCases\SuspendSimulatorHandler;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulationSessionRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulatorRepository;

it('registra el repositorio de simuladores en el contenedor', function (): void {
    expect(app(SimulatorRepository::class))->toBeInstanceOf(EloquentSimulatorRepository::class);
});

it('registra el repositorio de sesiones de simulacion en el contenedor', function (): void {
    expect(app(SimulationSessionRepository::class))->toBeInstanceOf(EloquentSimulationSessionRepository::class);
});

it('registra los handlers CQRS de simuladores en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(RegisterSimulatorCommand::class))->toBe(RegisterSimulatorHandler::class)
        ->and($registry->handlerFor(SuspendSimulatorCommand::class))->toBe(SuspendSimulatorHandler::class)
        ->and($registry->handlerFor(ReactivateSimulatorCommand::class))->toBe(ReactivateSimulatorHandler::class)
        ->and($registry->handlerFor(RetireSimulatorCommand::class))->toBe(RetireSimulatorHandler::class)
        ->and($registry->handlerFor(RotateSimulatorIntegrationKeyCommand::class))->toBe(RotateSimulatorIntegrationKeyHandler::class)
        ->and($registry->handlerFor(GetSimulatorQuery::class))->toBe(GetSimulatorHandler::class)
        ->and($registry->handlerFor(ListSimulatorsQuery::class))->toBe(ListSimulatorsHandler::class);
});

it('registra los handlers CQRS de sesiones de simulacion en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(ScheduleSimulationSessionCommand::class))->toBe(ScheduleSimulationSessionHandler::class)
        ->and($registry->handlerFor(StartSimulationSessionCommand::class))->toBe(StartSimulationSessionHandler::class)
        ->and($registry->handlerFor(CompleteSimulationSessionCommand::class))->toBe(CompleteSimulationSessionHandler::class)
        ->and($registry->handlerFor(CancelSimulationSessionCommand::class))->toBe(CancelSimulationSessionHandler::class)
        ->and($registry->handlerFor(GetSimulationSessionQuery::class))->toBe(GetSimulationSessionHandler::class)
        ->and($registry->handlerFor(GetMySimulationSessionsQuery::class))->toBe(GetMySimulationSessionsHandler::class)
        ->and($registry->handlerFor(ListSimulationSessionsQuery::class))->toBe(ListSimulationSessionsHandler::class);
});

it('registra los handlers CQRS de telemetria en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(SubmitTelemetryCommand::class))->toBe(SubmitTelemetryHandler::class)
        ->and($registry->handlerFor(GetSessionTelemetryQuery::class))->toBe(GetSessionTelemetryHandler::class);
});

it('registra el handler CQRS de resultados practicos en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(GetPracticalResultQuery::class))->toBe(GetPracticalResultHandler::class);
});
