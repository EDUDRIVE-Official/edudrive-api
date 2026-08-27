<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Commands\UpdateNotificationPreferenceCommand;
use Modules\Notification\Application\Responses\NotificationPreferenceResponse;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;

final readonly class UpdateNotificationPreferenceHandler
{
    public function __construct(private NotificationPreferenceRepository $preferences) {}

    public function handle(UpdateNotificationPreferenceCommand $command): NotificationPreferenceResponse
    {
        $preference = $this->preferences->findByUserId($command->userId)
            ?? NotificationPreference::default($command->userId);

        $preference->update(
            allowedChannels: array_map(
                static fn (string $channel): NotificationChannel => NotificationChannel::from($channel),
                $command->allowedChannels,
            ),
            mutedCategories: $command->mutedCategories,
            frequency: NotificationFrequency::from($command->frequency),
            quietHoursStart: $command->quietHoursStart,
            quietHoursEnd: $command->quietHoursEnd,
        );

        $this->preferences->save($preference);

        return NotificationPreferenceResponse::fromNotificationPreference($preference);
    }
}
