<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;
use Modules\Notification\Domain\Exceptions\InvalidNotificationTransition;
use Modules\Notification\Domain\ValueObjects\NotificationId;

final class Notification
{
    private function __construct(
        private NotificationId $id,
        private string $userId,
        private NotificationChannel $channel,
        private string $category,
        private string $subject,
        private string $body,
        private ?string $actionUrl,
        private NotificationStatus $status,
        private DateTimeImmutable $sentAt,
        private ?DateTimeImmutable $readAt,
    ) {}

    public static function send(
        NotificationId $id,
        string $userId,
        NotificationChannel $channel,
        string $category,
        string $subject,
        string $body,
        ?DateTimeImmutable $sentAt = null,
        ?string $actionUrl = null,
    ): self {
        return new self(
            $id,
            $userId,
            $channel,
            $category,
            $subject,
            $body,
            self::internalActionUrl($actionUrl),
            NotificationStatus::Unread,
            $sentAt ?? new DateTimeImmutable('now'),
            null,
        );
    }

    public static function restore(
        NotificationId $id,
        string $userId,
        NotificationChannel $channel,
        string $category,
        string $subject,
        string $body,
        NotificationStatus $status,
        DateTimeImmutable $sentAt,
        ?DateTimeImmutable $readAt,
        ?string $actionUrl = null,
    ): self {
        return new self($id, $userId, $channel, $category, $subject, $body, self::internalActionUrl($actionUrl), $status, $sentAt, $readAt);
    }

    public function markAsRead(DateTimeImmutable $at): void
    {
        if ($this->status === NotificationStatus::Read) {
            throw InvalidNotificationTransition::create();
        }

        $this->status = NotificationStatus::Read;
        $this->readAt = $at;
    }

    public function id(): NotificationId
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function actionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function status(): NotificationStatus
    {
        return $this->status;
    }

    public function sentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function readAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }

    private static function internalActionUrl(?string $actionUrl): ?string
    {
        if ($actionUrl === null) {
            return null;
        }

        $actionUrl = trim($actionUrl);
        if ($actionUrl === '' || strlen($actionUrl) > 1000 || ! str_starts_with($actionUrl, '/') || str_starts_with($actionUrl, '//')) {
            throw new InvalidArgumentException('El destino de la notificación debe ser una ruta interna segura.');
        }

        return $actionUrl;
    }
}
