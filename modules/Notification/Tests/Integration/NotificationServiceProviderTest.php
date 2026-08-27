<?php

declare(strict_types=1);

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

it('registra el repositorio de notificaciones en el contenedor', function (): void {
    expect(app(NotificationRepository::class))->toBeInstanceOf(EloquentNotificationRepository::class);
});

it('registra el repositorio de preferencias en el contenedor', function (): void {
    expect(app(NotificationPreferenceRepository::class))->toBeInstanceOf(EloquentNotificationPreferenceRepository::class);
});

it('registra los handlers CQRS de notificaciones en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(SendNotificationCommand::class))->toBe(SendNotificationHandler::class)
        ->and($registry->handlerFor(MarkNotificationAsReadCommand::class))->toBe(MarkNotificationAsReadHandler::class)
        ->and($registry->handlerFor(GetMyNotificationsQuery::class))->toBe(GetMyNotificationsHandler::class);
});

it('registra los handlers CQRS de preferencias en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(UpdateNotificationPreferenceCommand::class))->toBe(UpdateNotificationPreferenceHandler::class)
        ->and($registry->handlerFor(GiveNotificationConsentCommand::class))->toBe(GiveNotificationConsentHandler::class)
        ->and($registry->handlerFor(RevokeNotificationConsentCommand::class))->toBe(RevokeNotificationConsentHandler::class)
        ->and($registry->handlerFor(GetMyNotificationPreferenceQuery::class))->toBe(GetMyNotificationPreferenceHandler::class);
});

it('registra el repositorio de plantillas en el contenedor', function (): void {
    expect(app(CommunicationTemplateRepository::class))->toBeInstanceOf(EloquentCommunicationTemplateRepository::class);
});

it('registra los handlers CQRS de plantillas en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(CreateCommunicationTemplateCommand::class))->toBe(CreateCommunicationTemplateHandler::class)
        ->and($registry->handlerFor(UpdateCommunicationTemplateCommand::class))->toBe(UpdateCommunicationTemplateHandler::class)
        ->and($registry->handlerFor(RetireCommunicationTemplateCommand::class))->toBe(RetireCommunicationTemplateHandler::class)
        ->and($registry->handlerFor(GetCommunicationTemplateQuery::class))->toBe(GetCommunicationTemplateHandler::class)
        ->and($registry->handlerFor(ListCommunicationTemplatesQuery::class))->toBe(ListCommunicationTemplatesHandler::class)
        ->and($registry->handlerFor(PreviewCommunicationTemplateQuery::class))->toBe(PreviewCommunicationTemplateHandler::class);
});
