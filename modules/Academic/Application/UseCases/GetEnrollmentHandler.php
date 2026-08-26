<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentQuery;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final readonly class GetEnrollmentHandler
{
    public function __construct(private EnrollmentRepository $enrollments) {}

    public function handle(GetEnrollmentQuery $query): EnrollmentResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        return EnrollmentResponse::fromEnrollment($enrollment);
    }
}
