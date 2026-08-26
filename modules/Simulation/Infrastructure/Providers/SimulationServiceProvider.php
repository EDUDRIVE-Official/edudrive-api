<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );
    }
}
