<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use DateTimeImmutable;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Exceptions\NotificationNotFound;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;

final readonly class MarkNotificationAsReadHandler
{
    public function __construct(private NotificationRepository $notifications) {}

    public function handle(MarkNotificationAsReadCommand $command): NotificationResponse
    {
        $notification = $this->notifications->findById(NotificationId::fromString($command->notificationId));

        if ($notification === null || $notification->userId() !== $command->userId) {
            throw NotificationNotFound::withId($command->notificationId);
        }

        $notification->markAsRead(new DateTimeImmutable('now'));
        $this->notifications->save($notification);

        return NotificationResponse::fromNotification($notification);
    }
}
