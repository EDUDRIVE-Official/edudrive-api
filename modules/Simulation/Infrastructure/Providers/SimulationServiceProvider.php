<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Repositories\EloquentSimulatorRepository;

final class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SimulatorRepository::class, EloquentSimulatorRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
