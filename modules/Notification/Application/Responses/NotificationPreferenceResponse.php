<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Responses;

use DateTimeInterface;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;

final readonly class NotificationPreferenceResponse
{
    /**
     * @param  list<string>  $allowedChannels
     * @param  list<string>  $mutedCategories
     */
    public function __construct(
        public string $userId,
        public array $allowedChannels,
        public array $mutedCategories,
        public string $frequency,
        public ?string $quietHoursStart,
        public ?string $quietHoursEnd,
        public bool $consentGiven,
        public ?string $consentUpdatedAt,
    ) {}

    public static function fromNotificationPreference(NotificationPreference $preference): self
    {
        return new self(
            userId: $preference->userId(),
            allowedChannels: array_map(
                static fn (NotificationChannel $channel): string => $channel->value,
                $preference->allowedChannels(),
            ),
            mutedCategories: $preference->mutedCategories(),
            frequency: $preference->frequency()->value,
            quietHoursStart: $preference->quietHoursStart(),
            quietHoursEnd: $preference->quietHoursEnd(),
            consentGiven: $preference->consentGiven(),
            consentUpdatedAt: $preference->consentUpdatedAt()?->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'allowed_channels' => $this->allowedChannels,
            'muted_categories' => $this->mutedCategories,
            'frequency' => $this->frequency,
            'quiet_hours_start' => $this->quietHoursStart,
            'quiet_hours_end' => $this->quietHoursEnd,
            'consent_given' => $this->consentGiven,
            'consent_updated_at' => $this->consentUpdatedAt,
        ];
    }
}
