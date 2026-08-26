<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

interface EnrollmentRepository
{
    public function save(Enrollment $enrollment): void;

    public function findById(EnrollmentId $id): ?Enrollment;

    public function findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment;

    /**
     * @return list<Enrollment>
     */
    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array;
}
