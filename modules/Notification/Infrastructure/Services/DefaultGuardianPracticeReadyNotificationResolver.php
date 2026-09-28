<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Services;

use DateTimeImmutable;
use Modules\Notification\Application\Services\GuardianPracticeReadyNotificationResolver;
use Modules\Notification\Domain\Enums\NotificationStatus;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class DefaultGuardianPracticeReadyNotificationResolver implements GuardianPracticeReadyNotificationResolver
{
    public function __construct(private NotificationRepository $notifications) {}

    public function resolve(string $guardianUserId, string $minorUserId, string $lessonId): void
    {
        $actionUrl = '/mi-acompanamiento/'.$minorUserId.'?lesson_id='.$lessonId.'#practice-'.$lessonId;

        foreach ($this->notifications->allForUser($guardianUserId) as $notification) {
            if ($notification->category() !== 'practica_lista'
                || $notification->actionUrl() !== $actionUrl
                || $notification->status() !== NotificationStatus::Unread) {
                continue;
            }

            $notification->markAsRead(new DateTimeImmutable('now'));
            $this->notifications->save($notification);
        }
    }
}
