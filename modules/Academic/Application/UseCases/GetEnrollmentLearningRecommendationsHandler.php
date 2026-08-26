<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentLearningRecommendationsQuery;
use Modules\Academic\Application\Responses\LearningRecommendationsResponse;
use Modules\Academic\Application\Services\EnrollmentLearningRecommendationService;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final readonly class GetEnrollmentLearningRecommendationsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private EnrollmentLearningRecommendationService $recommendations,
    ) {}

    public function handle(GetEnrollmentLearningRecommendationsQuery $query): LearningRecommendationsResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());

        return $this->recommendations->build($enrollment, $progress);
    }
}
