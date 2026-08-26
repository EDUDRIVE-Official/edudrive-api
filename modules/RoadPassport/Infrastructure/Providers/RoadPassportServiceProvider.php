<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoadPassportRepository;

final class RoadPassportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoadPassportRepository::class, EloquentRoadPassportRepository::class);
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
