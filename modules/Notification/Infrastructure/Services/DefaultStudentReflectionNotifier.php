<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Services;

use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Services\StudentReflectionNotifier;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class DefaultStudentReflectionNotifier implements StudentReflectionNotifier
{
    public function __construct(
        private NotificationRepository $notifications,
        private SendNotificationHandler $sender,
    ) {}

    public function notify(string $guardianUserId, string $minorName, string $lessonTitle): void
    {
        $subject = $minorName.' respondió sobre '.$lessonTitle;
        foreach ($this->notifications->allForUser($guardianUserId) as $notification) {
            if ($notification->category() === 'reflexion_acompañamiento' && $notification->subject() === $subject) {
                return;
            }
        }

        $this->sender->handle(new SendNotificationCommand(
            userId: $guardianUserId,
            channel: 'web',
            category: 'reflexion_acompañamiento',
            subject: $subject,
            body: 'El estudiante completó su reflexión sobre la práctica acompañada. Podés leer qué aprendió y qué hará la próxima vez desde Mi acompañamiento.',
        ));
    }
}
