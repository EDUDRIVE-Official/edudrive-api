<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemSettingRepository;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemSettingRepository::class, EloquentSystemSettingRepository::class);
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
