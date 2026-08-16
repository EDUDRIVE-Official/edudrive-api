<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final readonly class GetEnrollmentProgressHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private EnrollmentProgressCalculator $calculator,
    ) {}

    public function handle(GetEnrollmentProgressQuery $query): EnrollmentProgressResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());

        return $this->calculator->calculate($enrollment, $progress);
    }
}
