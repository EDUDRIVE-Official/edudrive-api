<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Responses;

use DateTimeInterface;
use Modules\Notification\Domain\Aggregates\Notification;

final readonly class NotificationResponse
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $channel,
        public string $category,
        public string $subject,
        public string $body,
        public string $status,
        public string $sentAt,
        public ?string $readAt,
    ) {}

    public static function fromNotification(Notification $notification): self
    {
        return new self(
            id: $notification->id()->value(),
            userId: $notification->userId(),
            channel: $notification->channel()->value,
            category: $notification->category(),
            subject: $notification->subject(),
            body: $notification->body(),
            status: $notification->status()->value,
            sentAt: $notification->sentAt()->format(DateTimeInterface::ATOM),
            readAt: $notification->readAt()?->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'channel' => $this->channel,
            'category' => $this->category,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'sent_at' => $this->sentAt,
            'read_at' => $this->readAt,
        ];
    }
}
