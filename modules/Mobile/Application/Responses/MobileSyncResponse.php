<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Academic\Application\Responses\EnrollmentListItemResponse;
use Modules\Notification\Application\Responses\NotificationResponse;

final readonly class MobileSyncResponse
{
    /**
     * @param  list<EnrollmentListItemResponse>  $enrollments
     * @param  list<NotificationResponse>  $notifications
     */
    public function __construct(
        public array $enrollments,
        public array $notifications,
        public string $syncedAt,
    ) {}

    /**
     * @param  list<EnrollmentListItemResponse>  $enrollments
     * @param  list<NotificationResponse>  $notifications
     */
    public static function build(array $enrollments, array $notifications, DateTimeImmutable $syncedAt): self
    {
        return new self($enrollments, $notifications, $syncedAt->format(DateTimeInterface::ATOM));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'enrollments' => array_map(
                static fn (EnrollmentListItemResponse $enrollment): array => $enrollment->toArray(),
                $this->enrollments,
            ),
            'notifications' => array_map(
                static fn (NotificationResponse $notification): array => $notification->toArray(),
                $this->notifications,
            ),
            'synced_at' => $this->syncedAt,
        ];
    }
}
