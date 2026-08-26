<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class GetMyNotificationsHandler
{
    public function __construct(private NotificationRepository $notifications) {}

    /** @return list<NotificationResponse> */
    public function handle(GetMyNotificationsQuery $query): array
    {
        return array_map(
            static fn (Notification $notification): NotificationResponse => NotificationResponse::fromNotification($notification),
            $this->notifications->allForUser($query->userId),
        );
    }
}
