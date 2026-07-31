<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Application\UseCases\AssignRoleHandler;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Services\RoleAssignmentPermissionChecker;
use Modules\Authorization\Presentation\Console\AssignRoleConsoleCommand;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RoleAssignmentRepository::class,
            EloquentRoleAssignmentRepository::class,
        );

        $this->app->bind(
            PermissionChecker::class,
            RoleAssignmentPermissionChecker::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(
            AssignRoleCommand::class,
            AssignRoleHandler::class,
        );

        $this->commands([
            AssignRoleConsoleCommand::class,
        ]);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
