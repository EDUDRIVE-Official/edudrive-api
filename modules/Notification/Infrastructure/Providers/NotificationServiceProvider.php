<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Notification\Application\Commands\CreateCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\GiveNotificationConsentCommand;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\RetireCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\RevokeNotificationConsentCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Commands\UpdateCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\UpdateNotificationPreferenceCommand;
use Modules\Notification\Application\Queries\GetCommunicationTemplateQuery;
use Modules\Notification\Application\Queries\GetMyNotificationPreferenceQuery;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\Queries\ListCommunicationTemplatesQuery;
use Modules\Notification\Application\Queries\PreviewCommunicationTemplateQuery;
use Modules\Notification\Application\Services\EmailNotificationSender;
use Modules\Notification\Application\UseCases\CreateCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\GetCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\GetMyNotificationPreferenceHandler;
use Modules\Notification\Application\UseCases\GetMyNotificationsHandler;
use Modules\Notification\Application\UseCases\GiveNotificationConsentHandler;
use Modules\Notification\Application\UseCases\ListCommunicationTemplatesHandler;
use Modules\Notification\Application\UseCases\MarkNotificationAsReadHandler;
use Modules\Notification\Application\UseCases\PreviewCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\RetireCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\RevokeNotificationConsentHandler;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Application\UseCases\UpdateCommunicationTemplateHandler;
use Modules\Notification\Application\UseCases\UpdateNotificationPreferenceHandler;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentCommunicationTemplateRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationPreferenceRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;
use Modules\Notification\Infrastructure\Services\QueuedEmailNotificationSender;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationRepository::class, EloquentNotificationRepository::class);
        $this->app->bind(NotificationPreferenceRepository::class, EloquentNotificationPreferenceRepository::class);
        $this->app->bind(CommunicationTemplateRepository::class, EloquentCommunicationTemplateRepository::class);
        $this->app->bind(EmailNotificationSender::class, QueuedEmailNotificationSender::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(SendNotificationCommand::class, SendNotificationHandler::class);
        $registry->register(MarkNotificationAsReadCommand::class, MarkNotificationAsReadHandler::class);
        $registry->register(GetMyNotificationsQuery::class, GetMyNotificationsHandler::class);

        $registry->register(UpdateNotificationPreferenceCommand::class, UpdateNotificationPreferenceHandler::class);
        $registry->register(GiveNotificationConsentCommand::class, GiveNotificationConsentHandler::class);
        $registry->register(RevokeNotificationConsentCommand::class, RevokeNotificationConsentHandler::class);
        $registry->register(GetMyNotificationPreferenceQuery::class, GetMyNotificationPreferenceHandler::class);

        $registry->register(CreateCommunicationTemplateCommand::class, CreateCommunicationTemplateHandler::class);
        $registry->register(UpdateCommunicationTemplateCommand::class, UpdateCommunicationTemplateHandler::class);
        $registry->register(RetireCommunicationTemplateCommand::class, RetireCommunicationTemplateHandler::class);
        $registry->register(GetCommunicationTemplateQuery::class, GetCommunicationTemplateHandler::class);
        $registry->register(ListCommunicationTemplatesQuery::class, ListCommunicationTemplatesHandler::class);
        $registry->register(PreviewCommunicationTemplateQuery::class, PreviewCommunicationTemplateHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
