<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Notification\Application\Commands\GiveNotificationConsentCommand;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\RevokeNotificationConsentCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Commands\UpdateNotificationPreferenceCommand;
use Modules\Notification\Application\Queries\GetMyNotificationPreferenceQuery;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\UseCases\GetMyNotificationPreferenceHandler;
use Modules\Notification\Application\UseCases\GetMyNotificationsHandler;
use Modules\Notification\Application\UseCases\GiveNotificationConsentHandler;
use Modules\Notification\Application\UseCases\MarkNotificationAsReadHandler;
use Modules\Notification\Application\UseCases\RevokeNotificationConsentHandler;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Application\UseCases\UpdateNotificationPreferenceHandler;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Domain\Repositories\NotificationRepository;
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
