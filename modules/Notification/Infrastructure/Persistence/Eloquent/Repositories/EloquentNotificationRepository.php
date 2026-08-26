<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;

final readonly class EloquentNotificationRepository implements NotificationRepository
{
    public function save(Notification $notification): void
    {
        NotificationModel::query()->updateOrCreate(
            ['id' => $notification->id()->value()],
            [
                'user_id' => $notification->userId(),
                'channel' => $notification->channel()->value,
                'category' => $notification->category(),
                'subject' => $notification->subject(),
                'body' => $notification->body(),
                'status' => $notification->status()->value,
                'sent_at' => $notification->sentAt(),
                'read_at' => $notification->readAt(),
            ],
        );
    }

    public function findById(NotificationId $id): ?Notification
    {
        $model = NotificationModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Notification> */
    public function allForUser(string $userId): array
    {
        return array_values(
            NotificationModel::query()
                ->where('user_id', $userId)
                ->orderBy('sent_at')
                ->get()
                ->map(fn (NotificationModel $model): Notification => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(NotificationModel $model): Notification
    {
        $readAt = $model->getAttribute('read_at');

        return Notification::restore(
            id: NotificationId::fromString((string) $model->getAttribute('id')),
            userId: (string) $model->getAttribute('user_id'),
            channel: NotificationChannel::from((string) $model->getAttribute('channel')),
            category: (string) $model->getAttribute('category'),
            subject: (string) $model->getAttribute('subject'),
            body: (string) $model->getAttribute('body'),
            status: NotificationStatus::from((string) $model->getAttribute('status')),
            sentAt: new DateTimeImmutable((string) $model->getAttribute('sent_at')),
            readAt: $readAt === null ? null : new DateTimeImmutable((string) $readAt),
        );
    }
}
