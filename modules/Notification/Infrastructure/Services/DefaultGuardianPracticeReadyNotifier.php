<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Services;

use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\GuardianRelationshipModel;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Services\GuardianPracticeReadyNotifier;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class DefaultGuardianPracticeReadyNotifier implements GuardianPracticeReadyNotifier
{
    public function __construct(private NotificationRepository $notifications, private SendNotificationHandler $sender) {}

    public function notifyForMinor(string $minorUserId, string $lessonId, string $lessonTitle): void
    {
        $guardianIds = GuardianRelationshipModel::query()
            ->where('minor_user_id', $minorUserId)
            ->whereNull('revoked_at')
            ->pluck('guardian_user_id')
            ->unique();

        foreach ($guardianIds as $guardianId) {
            $guardianId = (string) $guardianId;
            $subject = 'Práctica lista para observar: '.$lessonTitle;
            $actionUrl = '/mi-acompanamiento/'.$minorUserId.'?lesson_id='.$lessonId.'#practice-'.$lessonId;
            $alreadySent = collect($this->notifications->allForUser($guardianId))->contains(
                fn ($notification): bool => $notification->category() === 'practica_lista'
                    && $notification->actionUrl() === $actionUrl,
            );
            if ($alreadySent) {
                continue;
            }

            $this->sender->handle(new SendNotificationCommand(
                userId: $guardianId,
                channel: 'web',
                category: 'practica_lista',
                subject: $subject,
                body: 'El estudiante completó la parte digital. Entrá a Mi acompañamiento para observar la práctica segura y registrar la evidencia cuando corresponda.',
                actionUrl: $actionUrl,
            ));
        }
    }
}
