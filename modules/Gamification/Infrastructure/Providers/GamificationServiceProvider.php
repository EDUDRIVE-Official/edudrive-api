<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserAchievementRepository;

final class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AchievementRepository::class, EloquentAchievementRepository::class);
        $this->app->bind(UserAchievementRepository::class, EloquentUserAchievementRepository::class);
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
