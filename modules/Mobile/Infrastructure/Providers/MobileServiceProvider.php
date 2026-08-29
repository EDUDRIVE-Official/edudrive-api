<?php

declare(strict_types=1);

namespace Modules\Mobile\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Mobile\Application\Commands\RegisterMobileDeviceCommand;
use Modules\Mobile\Application\Commands\RemoveMobileDeviceCommand;
use Modules\Mobile\Application\Queries\GetMobileSyncQuery;
use Modules\Mobile\Application\Queries\ListMobileDevicesQuery;
use Modules\Mobile\Application\Services\MobilePushSender;
use Modules\Mobile\Application\UseCases\GetMobileSyncHandler;
use Modules\Mobile\Application\UseCases\ListMobileDevicesHandler;
use Modules\Mobile\Application\UseCases\RegisterMobileDeviceHandler;
use Modules\Mobile\Application\UseCases\RemoveMobileDeviceHandler;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Infrastructure\Persistence\Eloquent\Repositories\EloquentMobileDeviceRepository;
use Modules\Mobile\Infrastructure\Services\QueuedMobilePushSender;

final class MobileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MobileDeviceRepository::class, EloquentMobileDeviceRepository::class);
        $this->app->bind(MobilePushSender::class, QueuedMobilePushSender::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(RegisterMobileDeviceCommand::class, RegisterMobileDeviceHandler::class);
        $registry->register(RemoveMobileDeviceCommand::class, RemoveMobileDeviceHandler::class);
        $registry->register(ListMobileDevicesQuery::class, ListMobileDevicesHandler::class);
        $registry->register(GetMobileSyncQuery::class, GetMobileSyncHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
