<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
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
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulationSessionRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulatorRepository;

final class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SimulatorRepository::class, EloquentSimulatorRepository::class);
        $this->app->bind(SimulationSessionRepository::class, EloquentSimulationSessionRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(RegisterSimulatorCommand::class, RegisterSimulatorHandler::class);
        $registry->register(SuspendSimulatorCommand::class, SuspendSimulatorHandler::class);
        $registry->register(ReactivateSimulatorCommand::class, ReactivateSimulatorHandler::class);
        $registry->register(RetireSimulatorCommand::class, RetireSimulatorHandler::class);
        $registry->register(RotateSimulatorIntegrationKeyCommand::class, RotateSimulatorIntegrationKeyHandler::class);
        $registry->register(GetSimulatorQuery::class, GetSimulatorHandler::class);
        $registry->register(ListSimulatorsQuery::class, ListSimulatorsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
