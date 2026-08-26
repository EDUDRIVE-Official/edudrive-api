<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;

final readonly class SendNotificationHandler
{
    public function __construct(private NotificationRepository $notifications) {}

    public function handle(SendNotificationCommand $command): NotificationResponse
    {
        $notification = Notification::send(
            id: NotificationId::fromString((string) Str::uuid()),
            userId: $command->userId,
            channel: NotificationChannel::from($command->channel),
            category: $command->category,
            subject: $command->subject,
            body: $command->body,
        );

        $this->notifications->save($notification);

        return NotificationResponse::fromNotification($notification);
    }
}
