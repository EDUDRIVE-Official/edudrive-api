<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Queries\GetMyNotificationPreferenceQuery;
use Modules\Notification\Application\Responses\NotificationPreferenceResponse;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;

final readonly class GetMyNotificationPreferenceHandler
{
    public function __construct(private NotificationPreferenceRepository $preferences) {}

    public function handle(GetMyNotificationPreferenceQuery $query): NotificationPreferenceResponse
    {
        $preference = $this->preferences->findByUserId($query->userId)
            ?? NotificationPreference::default($query->userId);

        return NotificationPreferenceResponse::fromNotificationPreference($preference);
    }
}
