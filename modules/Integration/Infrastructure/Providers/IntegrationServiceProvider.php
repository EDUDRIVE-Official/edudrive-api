<?php

declare(strict_types=1);

namespace Modules\Integration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Integration\Application\Commands\ReactivateApiConsumerCommand;
use Modules\Integration\Application\Commands\RegisterApiConsumerCommand;
use Modules\Integration\Application\Commands\RevokeApiConsumerCommand;
use Modules\Integration\Application\Commands\RotateApiConsumerIntegrationKeyCommand;
use Modules\Integration\Application\Commands\SuspendApiConsumerCommand;
use Modules\Integration\Application\Queries\GetApiConsumerQuery;
use Modules\Integration\Application\Queries\ListApiConsumersQuery;
use Modules\Integration\Application\UseCases\GetApiConsumerHandler;
use Modules\Integration\Application\UseCases\ListApiConsumersHandler;
use Modules\Integration\Application\UseCases\ReactivateApiConsumerHandler;
use Modules\Integration\Application\UseCases\RegisterApiConsumerHandler;
use Modules\Integration\Application\UseCases\RevokeApiConsumerHandler;
use Modules\Integration\Application\UseCases\RotateApiConsumerIntegrationKeyHandler;
use Modules\Integration\Application\UseCases\SuspendApiConsumerHandler;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Infrastructure\Persistence\Eloquent\Repositories\EloquentApiConsumerRepository;

final class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApiConsumerRepository::class, EloquentApiConsumerRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(RegisterApiConsumerCommand::class, RegisterApiConsumerHandler::class);
        $registry->register(SuspendApiConsumerCommand::class, SuspendApiConsumerHandler::class);
        $registry->register(ReactivateApiConsumerCommand::class, ReactivateApiConsumerHandler::class);
        $registry->register(RevokeApiConsumerCommand::class, RevokeApiConsumerHandler::class);
        $registry->register(RotateApiConsumerIntegrationKeyCommand::class, RotateApiConsumerIntegrationKeyHandler::class);
        $registry->register(GetApiConsumerQuery::class, GetApiConsumerHandler::class);
        $registry->register(ListApiConsumersQuery::class, ListApiConsumersHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
