<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Exceptions\UnitLocked;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class CompleteLessonHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
        private EnrollmentProgressCalculator $calculator,
    ) {}

    public function handle(CompleteLessonCommand $command): EnrollmentProgressResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($command->enrollmentId));
        if ($enrollment === null || $enrollment->userId() !== $command->userId) {
            throw EnrollmentNotFound::withId($command->enrollmentId);
        }

        if ($enrollment->status() !== EnrollmentStatus::Active) {
            throw InvalidEnrollment::create();
        }

        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $lessonId = LessonId::fromString($command->lessonId);
        if (! in_array($lessonId->value(), $this->lessonCatalog->lessonIdsFor($course), true)) {
            throw LessonNotFound::withId($command->lessonId);
        }

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());

        $unlockStatus = $this->unlockCalculator->statusFor($course, $progress);
        $unitId = $unlockStatus->unitIdForLesson($lessonId);
        if ($unitId !== null && ! $unlockStatus->isUnitUnlocked($unitId)) {
            throw UnitLocked::withId($unitId->value());
        }

        $progress->completeLesson($lessonId, new DateTimeImmutable('now'), $command->timeSpentMinutes);
        $this->progressRepository->save($progress);

        return $this->calculator->calculate($enrollment, $progress);
    }
}
