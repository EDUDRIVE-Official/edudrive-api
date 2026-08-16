<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\ModuleUnlockStatus;
use Modules\Academic\Domain\ValueObjects\UnitUnlockStatus;

final readonly class GetEnrollmentCurriculumStatusHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private CourseRepository $courses,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
    ) {}

    public function handle(GetEnrollmentCurriculumStatusQuery $query): CurriculumUnlockResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());
        $status = $this->unlockCalculator->statusFor($course, $progress);

        return new CurriculumUnlockResponse(
            enrollmentId: $enrollment->id()->value(),
            courseId: $enrollment->courseId()->value(),
            modules: array_map(
                static fn (ModuleUnlockStatus $module): array => [
                    'module_id' => $module->moduleId->value(),
                    'completed' => $module->completed,
                    'unlocked' => $module->unlocked,
                    'units' => array_map(
                        static fn (UnitUnlockStatus $unit): array => [
                            'unit_id' => $unit->unitId->value(),
                            'completed' => $unit->completed,
                            'unlocked' => $unit->unlocked,
                        ],
                        $module->units,
                    ),
                ],
                $status->modules,
            ),
        );
    }
}
