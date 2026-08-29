<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Analytics\Application\Commands\RequestAnalyticsReportCommand;
use Modules\Analytics\Application\UseCases\RequestAnalyticsReportHandler;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(RequestAnalyticsReportCommand::class, RequestAnalyticsReportHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );
    }
}
