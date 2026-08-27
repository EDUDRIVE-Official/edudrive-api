<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;

final class NotificationPreference
{
    /**
     * @param  list<NotificationChannel>  $allowedChannels
     * @param  list<string>  $mutedCategories
     */
    private function __construct(
        private string $userId,
        private array $allowedChannels,
        private array $mutedCategories,
        private NotificationFrequency $frequency,
        private ?string $quietHoursStart,
        private ?string $quietHoursEnd,
        private bool $consentGiven,
        private ?DateTimeImmutable $consentUpdatedAt,
    ) {}

    public static function default(string $userId): self
    {
        return new self(
            $userId,
            NotificationChannel::cases(),
            [],
            NotificationFrequency::Immediate,
            null,
            null,
            true,
            null,
        );
    }

    /**
     * @param  list<NotificationChannel>  $allowedChannels
     * @param  list<string>  $mutedCategories
     */
    public static function restore(
        string $userId,
        array $allowedChannels,
        array $mutedCategories,
        NotificationFrequency $frequency,
        ?string $quietHoursStart,
        ?string $quietHoursEnd,
        bool $consentGiven,
        ?DateTimeImmutable $consentUpdatedAt,
    ): self {
        self::guardQuietHours($quietHoursStart, $quietHoursEnd);

        return new self($userId, $allowedChannels, $mutedCategories, $frequency, $quietHoursStart, $quietHoursEnd, $consentGiven, $consentUpdatedAt);
    }

    /**
     * @param  list<NotificationChannel>  $allowedChannels
     * @param  list<string>  $mutedCategories
     */
    public function update(
        array $allowedChannels,
        array $mutedCategories,
        NotificationFrequency $frequency,
        ?string $quietHoursStart,
        ?string $quietHoursEnd,
    ): void {
        self::guardQuietHours($quietHoursStart, $quietHoursEnd);

        $this->allowedChannels = $allowedChannels;
        $this->mutedCategories = $mutedCategories;
        $this->frequency = $frequency;
        $this->quietHoursStart = $quietHoursStart;
        $this->quietHoursEnd = $quietHoursEnd;
    }

    public function giveConsent(DateTimeImmutable $at): void
    {
        $this->consentGiven = true;
        $this->consentUpdatedAt = $at;
    }

    public function revokeConsent(DateTimeImmutable $at): void
    {
        $this->consentGiven = false;
        $this->consentUpdatedAt = $at;
    }

    public function allows(NotificationChannel $channel, string $category): bool
    {
        return $this->consentGiven
            && in_array($channel, $this->allowedChannels, true)
            && ! in_array($category, $this->mutedCategories, true);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    /** @return list<NotificationChannel> */
    public function allowedChannels(): array
    {
        return $this->allowedChannels;
    }

    /** @return list<string> */
    public function mutedCategories(): array
    {
        return $this->mutedCategories;
    }

    public function frequency(): NotificationFrequency
    {
        return $this->frequency;
    }

    public function quietHoursStart(): ?string
    {
        return $this->quietHoursStart;
    }

    public function quietHoursEnd(): ?string
    {
        return $this->quietHoursEnd;
    }

    public function consentGiven(): bool
    {
        return $this->consentGiven;
    }

    public function consentUpdatedAt(): ?DateTimeImmutable
    {
        return $this->consentUpdatedAt;
    }

    private static function guardQuietHours(?string $quietHoursStart, ?string $quietHoursEnd): void
    {
        if ($quietHoursStart === null && $quietHoursEnd === null) {
            return;
        }

        if ($quietHoursStart === null || $quietHoursEnd === null) {
            throw new InvalidArgumentException('El horario de silencio requiere hora de inicio y de fin, o ninguna de las dos.');
        }

        foreach ([$quietHoursStart, $quietHoursEnd] as $time) {
            if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
                throw new InvalidArgumentException('El horario de silencio debe tener el formato HH:MM.');
            }
        }
    }
}
