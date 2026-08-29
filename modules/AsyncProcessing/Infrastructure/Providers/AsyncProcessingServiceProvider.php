<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AsyncProcessing\Application\Queries\GetAsyncJobQuery;
use Modules\AsyncProcessing\Application\UseCases\GetAsyncJobHandler;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Infrastructure\Persistence\Eloquent\Repositories\EloquentAsyncJobRepository;
use Modules\AsyncProcessing\Presentation\Console\CleanupAsyncJobsCommand;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AsyncProcessingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AsyncJobRepository::class, EloquentAsyncJobRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(GetAsyncJobQuery::class, GetAsyncJobHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );

        $this->commands([
            CleanupAsyncJobsCommand::class,
        ]);
    }
}
