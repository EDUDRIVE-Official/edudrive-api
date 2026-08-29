<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Webhook\Application\Commands\ReactivateWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RegisterWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RetryWebhookDeliveryCommand;
use Modules\Webhook\Application\Commands\RotateWebhookSubscriptionSecretCommand;
use Modules\Webhook\Application\Commands\SuspendWebhookSubscriptionCommand;
use Modules\Webhook\Application\Queries\GetWebhookSubscriptionQuery;
use Modules\Webhook\Application\Queries\ListWebhookDeliveriesQuery;
use Modules\Webhook\Application\Queries\ListWebhookSubscriptionsQuery;
use Modules\Webhook\Application\Services\WebhookDeliveryDispatcher;
use Modules\Webhook\Application\Services\WebhookEventPublisher;
use Modules\Webhook\Application\UseCases\GetWebhookSubscriptionHandler;
use Modules\Webhook\Application\UseCases\ListWebhookDeliveriesHandler;
use Modules\Webhook\Application\UseCases\ListWebhookSubscriptionsHandler;
use Modules\Webhook\Application\UseCases\ReactivateWebhookSubscriptionHandler;
use Modules\Webhook\Application\UseCases\RegisterWebhookSubscriptionHandler;
use Modules\Webhook\Application\UseCases\RetryWebhookDeliveryHandler;
use Modules\Webhook\Application\UseCases\RotateWebhookSubscriptionSecretHandler;
use Modules\Webhook\Application\UseCases\SuspendWebhookSubscriptionHandler;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Infrastructure\Persistence\Eloquent\Repositories\EloquentWebhookDeliveryRepository;
use Modules\Webhook\Infrastructure\Persistence\Eloquent\Repositories\EloquentWebhookSubscriptionRepository;
use Modules\Webhook\Infrastructure\Services\LaravelWebhookDeliveryDispatcher;
use Modules\Webhook\Infrastructure\Services\QueuedWebhookEventPublisher;

final class WebhookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WebhookSubscriptionRepository::class, EloquentWebhookSubscriptionRepository::class);
        $this->app->bind(WebhookDeliveryRepository::class, EloquentWebhookDeliveryRepository::class);
        $this->app->bind(WebhookDeliveryDispatcher::class, LaravelWebhookDeliveryDispatcher::class);
        $this->app->bind(WebhookEventPublisher::class, QueuedWebhookEventPublisher::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(RegisterWebhookSubscriptionCommand::class, RegisterWebhookSubscriptionHandler::class);
        $registry->register(SuspendWebhookSubscriptionCommand::class, SuspendWebhookSubscriptionHandler::class);
        $registry->register(ReactivateWebhookSubscriptionCommand::class, ReactivateWebhookSubscriptionHandler::class);
        $registry->register(RotateWebhookSubscriptionSecretCommand::class, RotateWebhookSubscriptionSecretHandler::class);
        $registry->register(RetryWebhookDeliveryCommand::class, RetryWebhookDeliveryHandler::class);
        $registry->register(GetWebhookSubscriptionQuery::class, GetWebhookSubscriptionHandler::class);
        $registry->register(ListWebhookSubscriptionsQuery::class, ListWebhookSubscriptionsHandler::class);
        $registry->register(ListWebhookDeliveriesQuery::class, ListWebhookDeliveriesHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
