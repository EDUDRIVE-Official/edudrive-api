<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Mobile\Application\Services\MobilePushSender;
use Modules\Mobile\Domain\ValueObjects\MobilePushMessage;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Application\Services\EmailNotificationSender;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;

final readonly class SendNotificationHandler
{
    public function __construct(
        private NotificationRepository $notifications,
        private NotificationPreferenceRepository $preferences,
        private MobilePushSender $mobilePushSender,
        private EmailNotificationSender $emailNotificationSender,
    ) {}

    public function handle(SendNotificationCommand $command): ?NotificationResponse
    {
        $channel = NotificationChannel::from($command->channel);
        $preference = $this->preferences->findByUserId($command->userId)
            ?? NotificationPreference::default($command->userId);

        if (! $preference->allows($channel, $command->category)) {
            return null;
        }

        $notification = Notification::send(
            id: NotificationId::fromString((string) Str::uuid()),
            userId: $command->userId,
            channel: $channel,
            category: $command->category,
            subject: $command->subject,
            body: $command->body,
        );

        $this->notifications->save($notification);

        if ($channel === NotificationChannel::Mobile) {
            $this->mobilePushSender->send(new MobilePushMessage(
                userId: $command->userId,
                title: $command->subject,
                body: $command->body,
            ));
        } elseif ($channel === NotificationChannel::Email) {
            $this->emailNotificationSender->send($command->userId, $command->subject, $command->body);
        }

        return NotificationResponse::fromNotification($notification);
    }
}
