<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\UseCases\GetMyNotificationsHandler;
use Modules\Notification\Application\UseCases\MarkNotificationAsReadHandler;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;

it('registra el repositorio de notificaciones en el contenedor', function (): void {
    expect(app(NotificationRepository::class))->toBeInstanceOf(EloquentNotificationRepository::class);
});

it('registra los handlers CQRS de notificaciones en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(SendNotificationCommand::class))->toBe(SendNotificationHandler::class)
        ->and($registry->handlerFor(MarkNotificationAsReadCommand::class))->toBe(MarkNotificationAsReadHandler::class)
        ->and($registry->handlerFor(GetMyNotificationsQuery::class))->toBe(GetMyNotificationsHandler::class);
});
