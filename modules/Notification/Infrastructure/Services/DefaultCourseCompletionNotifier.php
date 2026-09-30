<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Services;

use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Services\CourseCompletionNotifier;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class DefaultCourseCompletionNotifier implements CourseCompletionNotifier
{
    public function __construct(
        private NotificationRepository $notifications,
        private SendNotificationHandler $sender,
    ) {}

    public function notify(string $userId, string $courseId, string $courseTitle): void
    {
        $subject = '¡Misión completada: '.$courseTitle.'!';
        foreach ($this->notifications->allForUser($userId) as $notification) {
            if ($notification->category() === 'curso' && $notification->subject() === $subject) {
                return;
            }
        }

        $this->sender->handle(new SendNotificationCommand(
            userId: $userId,
            channel: 'web',
            category: 'curso',
            subject: $subject,
            body: "Completaste todas las lecciones y prácticas. Ya recibiste tu certificado, 100 XP y la insignia «Primera misión cumplida».\n\nPodés consultar estos resultados en tu Pasaporte Vial, Mis certificados y Mi progreso.",
        ));
    }
}
