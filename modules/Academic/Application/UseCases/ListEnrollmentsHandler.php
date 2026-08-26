<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListEnrollmentsQuery;
use Modules\Academic\Application\Responses\EnrollmentListItemResponse;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ListEnrollmentsHandler
{
    public function __construct(private EnrollmentRepository $enrollments) {}

    /** @return list<EnrollmentListItemResponse> */
    public function handle(ListEnrollmentsQuery $query): array
    {
        return array_map(
            static fn (Enrollment $enrollment): EnrollmentListItemResponse => EnrollmentListItemResponse::fromEnrollment($enrollment),
            $this->enrollments->all(
                courseId: $query->courseId === null ? null : CourseId::fromString($query->courseId),
                userId: $query->userId,
                organizationId: $query->organizationId,
                status: $query->status === null ? null : EnrollmentStatus::from($query->status),
                source: $query->source === null ? null : EnrollmentSource::from($query->source),
            ),
        );
    }
}
