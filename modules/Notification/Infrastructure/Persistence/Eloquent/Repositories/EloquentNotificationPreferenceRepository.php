<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationPreferenceModel;

final readonly class EloquentNotificationPreferenceRepository implements NotificationPreferenceRepository
{
    public function save(NotificationPreference $preference): void
    {
        NotificationPreferenceModel::query()->updateOrCreate(
            ['user_id' => $preference->userId()],
            [
                'allowed_channels' => array_map(
                    static fn (NotificationChannel $channel): string => $channel->value,
                    $preference->allowedChannels(),
                ),
                'muted_categories' => $preference->mutedCategories(),
                'frequency' => $preference->frequency()->value,
                'quiet_hours_start' => $preference->quietHoursStart(),
                'quiet_hours_end' => $preference->quietHoursEnd(),
                'consent_given' => $preference->consentGiven(),
                'consent_updated_at' => $preference->consentUpdatedAt(),
            ],
        );
    }

    public function findByUserId(string $userId): ?NotificationPreference
    {
        $model = NotificationPreferenceModel::query()->where('user_id', $userId)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    private function toDomain(NotificationPreferenceModel $model): NotificationPreference
    {
        $consentUpdatedAt = $model->getAttribute('consent_updated_at');

        /** @var list<string> $allowedChannels */
        $allowedChannels = $model->getAttribute('allowed_channels');

        /** @var list<string> $mutedCategories */
        $mutedCategories = $model->getAttribute('muted_categories');

        return NotificationPreference::restore(
            userId: (string) $model->getAttribute('user_id'),
            allowedChannels: array_map(
                static fn (string $channel): NotificationChannel => NotificationChannel::from($channel),
                $allowedChannels,
            ),
            mutedCategories: $mutedCategories,
            frequency: NotificationFrequency::from((string) $model->getAttribute('frequency')),
            quietHoursStart: $model->getAttribute('quiet_hours_start'),
            quietHoursEnd: $model->getAttribute('quiet_hours_end'),
            consentGiven: (bool) $model->getAttribute('consent_given'),
            consentUpdatedAt: $consentUpdatedAt === null ? null : new DateTimeImmutable((string) $consentUpdatedAt),
        );
    }
}
