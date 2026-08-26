<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Simulation\Application\Commands\ReactivateSimulatorCommand;
use Modules\Simulation\Application\Commands\RegisterSimulatorCommand;
use Modules\Simulation\Application\Commands\RetireSimulatorCommand;
use Modules\Simulation\Application\Commands\RotateSimulatorIntegrationKeyCommand;
use Modules\Simulation\Application\Commands\SuspendSimulatorCommand;
use Modules\Simulation\Application\Queries\GetSimulatorQuery;
use Modules\Simulation\Application\Queries\ListSimulatorsQuery;
use Modules\Simulation\Application\UseCases\GetSimulatorHandler;
use Modules\Simulation\Application\UseCases\ListSimulatorsHandler;
use Modules\Simulation\Application\UseCases\ReactivateSimulatorHandler;
use Modules\Simulation\Application\UseCases\RegisterSimulatorHandler;
use Modules\Simulation\Application\UseCases\RetireSimulatorHandler;
use Modules\Simulation\Application\UseCases\RotateSimulatorIntegrationKeyHandler;
use Modules\Simulation\Application\UseCases\SuspendSimulatorHandler;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulatorRepository;

it('registra el repositorio de simuladores en el contenedor', function (): void {
    expect(app(SimulatorRepository::class))->toBeInstanceOf(EloquentSimulatorRepository::class);
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
