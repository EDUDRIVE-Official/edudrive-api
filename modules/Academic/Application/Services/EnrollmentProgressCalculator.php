<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use DateTimeImmutable;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;

final readonly class EnrollmentProgressCalculator
{
    public function __construct(
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private ExamRepository $exams,
        private ExamAttemptRepository $examAttempts,
    ) {}

    public function calculate(Enrollment $enrollment, EnrollmentProgress $progress): EnrollmentProgressResponse
    {
        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $totalLessons = count($this->lessonCatalog->lessonIdsFor($course));
        $completedLessonIds = $progress->completedLessonIds();
        $completedCount = count($completedLessonIds);

        [$evaluationsCompleted, $lastExamSubmittedAt] = $this->evaluationsFor($enrollment);

        $lastActivityAt = self::latest($progress->lastCompletedAt(), $lastExamSubmittedAt);

        return new EnrollmentProgressResponse(
            enrollmentId: $enrollment->id()->value(),
            courseId: $enrollment->courseId()->value(),
            userId: $enrollment->userId(),
            completedLessons: $completedLessonIds,
            completedLessonsCount: $completedCount,
            totalLessons: $totalLessons,
            progressPercentage: $totalLessons === 0 ? 0 : (int) round($completedCount / $totalLessons * 100),
            timeSpentMinutes: $progress->totalTimeSpentMinutes(),
            evaluationsCompleted: $evaluationsCompleted,
            lastActivityAt: $lastActivityAt?->format(DATE_ATOM),
        );
    }

    /** @return array{0: int, 1: ?DateTimeImmutable} */
    private function evaluationsFor(Enrollment $enrollment): array
    {
        $examIds = array_map(
            static fn (Exam $exam): string => $exam->id()->value(),
            $this->exams->all($enrollment->courseId()),
        );

        $count = 0;
        $lastSubmittedAt = null;

        foreach ($this->examAttempts->all(userId: $enrollment->userId(), status: ExamAttemptStatus::Submitted) as $attempt) {
            if (! in_array($attempt->examId()->value(), $examIds, true)) {
                continue;
            }

            $count++;
            $submittedAt = $attempt->submittedAt();
            if ($submittedAt !== null) {
                $lastSubmittedAt = self::latest($lastSubmittedAt, $submittedAt);
            }
        }

        return [$count, $lastSubmittedAt];
    }

    private static function latest(?DateTimeImmutable $a, ?DateTimeImmutable $b): ?DateTimeImmutable
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return $a > $b ? $a : $b;
    }
}
