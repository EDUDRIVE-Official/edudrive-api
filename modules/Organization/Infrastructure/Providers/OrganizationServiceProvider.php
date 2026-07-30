<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Organization\Application\Commands\AddCampusCommand;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\UseCases\AddCampusHandler;
use Modules\Organization\Application\UseCases\CreateOrganizationHandler;
use Modules\Organization\Application\UseCases\ListOrganizationsHandler;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationRepository;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrganizationRepository::class,
            EloquentOrganizationRepository::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(
            CreateOrganizationCommand::class,
            CreateOrganizationHandler::class,
        );

        $registry->register(
            AddCampusCommand::class,
            AddCampusHandler::class,
        );

        $registry->register(
            ListOrganizationsQuery::class,
            ListOrganizationsHandler::class,
        );

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
