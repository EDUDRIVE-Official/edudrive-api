<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use DateTimeImmutable;
use Modules\Notification\Application\Commands\GiveNotificationConsentCommand;
use Modules\Notification\Application\Responses\NotificationPreferenceResponse;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;

final readonly class GiveNotificationConsentHandler
{
    public function __construct(private NotificationPreferenceRepository $preferences) {}

    public function handle(GiveNotificationConsentCommand $command): NotificationPreferenceResponse
    {
        $preference = $this->preferences->findByUserId($command->userId)
            ?? NotificationPreference::default($command->userId);

        $preference->giveConsent(new DateTimeImmutable('now'));
        $this->preferences->save($preference);

        return NotificationPreferenceResponse::fromNotificationPreference($preference);
    }
}
