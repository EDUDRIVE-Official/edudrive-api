<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Simulation\Application\Commands\CancelSimulationSessionCommand;
use Modules\Simulation\Application\Commands\CompleteSimulationSessionCommand;
use Modules\Simulation\Application\Commands\ReactivateSimulatorCommand;
use Modules\Simulation\Application\Commands\RegisterSimulatorCommand;
use Modules\Simulation\Application\Commands\RetireSimulatorCommand;
use Modules\Simulation\Application\Commands\RotateSimulatorIntegrationKeyCommand;
use Modules\Simulation\Application\Commands\ScheduleSimulationSessionCommand;
use Modules\Simulation\Application\Commands\StartSimulationSessionCommand;
use Modules\Simulation\Application\Commands\SuspendSimulatorCommand;
use Modules\Simulation\Application\Queries\GetMySimulationSessionsQuery;
use Modules\Simulation\Application\Queries\GetSimulationSessionQuery;
use Modules\Simulation\Application\Queries\GetSimulatorQuery;
use Modules\Simulation\Application\Queries\ListSimulationSessionsQuery;
use Modules\Simulation\Application\Queries\ListSimulatorsQuery;
use Modules\Simulation\Application\UseCases\CancelSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\CompleteSimulationSessionHandler;
use Modules\Simulation\Application\UseCases\GetMySimulationSessionsHandler;
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
use Modules\Simulation\Application\UseCases\SuspendSimulatorHandler;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Repositories\TelemetrySampleRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulationSessionRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulatorRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentTelemetryEventRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentTelemetrySampleRepository;

final class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SimulatorRepository::class, EloquentSimulatorRepository::class);
        $this->app->bind(SimulationSessionRepository::class, EloquentSimulationSessionRepository::class);
        $this->app->bind(TelemetrySampleRepository::class, EloquentTelemetrySampleRepository::class);
        $this->app->bind(TelemetryEventRepository::class, EloquentTelemetryEventRepository::class);
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

        $registry->register(ScheduleSimulationSessionCommand::class, ScheduleSimulationSessionHandler::class);
        $registry->register(StartSimulationSessionCommand::class, StartSimulationSessionHandler::class);
        $registry->register(CompleteSimulationSessionCommand::class, CompleteSimulationSessionHandler::class);
        $registry->register(CancelSimulationSessionCommand::class, CancelSimulationSessionHandler::class);
        $registry->register(GetSimulationSessionQuery::class, GetSimulationSessionHandler::class);
        $registry->register(GetMySimulationSessionsQuery::class, GetMySimulationSessionsHandler::class);
        $registry->register(ListSimulationSessionsQuery::class, ListSimulationSessionsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
