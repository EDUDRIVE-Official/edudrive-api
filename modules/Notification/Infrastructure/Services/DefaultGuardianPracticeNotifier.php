<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Services;

use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Services\GuardianPracticeNotifier;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class DefaultGuardianPracticeNotifier implements GuardianPracticeNotifier
{
    public function __construct(
        private NotificationRepository $notifications,
        private SendNotificationHandler $sender,
    ) {}

    public function notify(string $userId, string $lessonId, string $lessonTitle, string $observationSubjectId): void
    {
        $subject = 'Nueva práctica acompañada: '.$lessonTitle;
        $actionUrl = '/mi-pasaporte-vial?observation='.$observationSubjectId.'#practice-reflection-'.$observationSubjectId;
        foreach ($this->notifications->allForUser($userId) as $notification) {
            if ($notification->category() === 'acompañamiento' && $notification->actionUrl() === $actionUrl) {
                return;
            }
        }

        $this->sender->handle(new SendNotificationCommand(
            userId: $userId,
            channel: 'web',
            category: 'acompañamiento',
            subject: $subject,
            body: 'Tu persona tutora registró una conducta segura que observó durante la práctica. Esta evidencia ya forma parte de tu Pasaporte Vial.',
            actionUrl: $actionUrl,
        ));
    }
}
