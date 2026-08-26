<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\UseCases\GetMyNotificationsHandler;
use Modules\Notification\Application\UseCases\MarkNotificationAsReadHandler;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationRepository::class, EloquentNotificationRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(SendNotificationCommand::class, SendNotificationHandler::class);
        $registry->register(MarkNotificationAsReadCommand::class, MarkNotificationAsReadHandler::class);
        $registry->register(GetMyNotificationsQuery::class, GetMyNotificationsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
