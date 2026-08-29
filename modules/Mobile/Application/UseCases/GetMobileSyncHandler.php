<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Responses\EnrollmentListItemResponse;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Mobile\Application\Queries\GetMobileSyncQuery;
use Modules\Mobile\Application\Responses\MobileSyncResponse;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Repositories\NotificationRepository;

final readonly class GetMobileSyncHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private NotificationRepository $notifications,
    ) {}

    public function handle(GetMobileSyncQuery $query): MobileSyncResponse
    {
        $since = $query->since === null ? null : new DateTimeImmutable($query->since);
        $now = new DateTimeImmutable('now');

        $enrollments = array_values(array_filter(
            $this->enrollments->all(userId: $query->userId),
            static fn (Enrollment $enrollment): bool => $since === null || $enrollment->enrolledAt() > $since,
        ));

        $notifications = array_values(array_filter(
            $this->notifications->allForUser($query->userId),
            static fn (Notification $notification): bool => $since === null || $notification->sentAt() > $since,
        ));

        return MobileSyncResponse::build(
            enrollments: array_map(
                static fn (Enrollment $enrollment): EnrollmentListItemResponse => EnrollmentListItemResponse::fromEnrollment($enrollment),
                $enrollments,
            ),
            notifications: array_map(
                static fn (Notification $notification): NotificationResponse => NotificationResponse::fromNotification($notification),
                $notifications,
            ),
            syncedAt: $now,
        );
    }
}
