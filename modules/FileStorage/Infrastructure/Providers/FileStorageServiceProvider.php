<?php

declare(strict_types=1);

namespace Modules\FileStorage\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Modules\FileStorage\Domain\Repositories\FileRepository;
use Modules\FileStorage\Infrastructure\Console\Commands\EnsureFileBucketExists;
use Modules\FileStorage\Infrastructure\Persistence\Eloquent\Repositories\EloquentFileRepository;
use Modules\FileStorage\Infrastructure\Storage\S3FileStorage;

final class FileStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileRepository::class, EloquentFileRepository::class);
        $this->app->bind(FileStorage::class, S3FileStorage::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                EnsureFileBucketExists::class,
            ]);
        }
    }
}
