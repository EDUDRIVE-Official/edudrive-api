<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Application\Responses\LearningRecommendationsResponse;
use Modules\Academic\Application\Responses\RetryableExamResponse;
use Modules\Academic\Application\Responses\StudyRecommendationResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class EnrollmentLearningRecommendationService
{
    private const MAX_WEAK_COMPETENCIES = 5;

    public function __construct(
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
        private ExamRepository $exams,
        private ExamAttemptRepository $examAttempts,
    ) {}

    public function build(Enrollment $enrollment, EnrollmentProgress $progress): LearningRecommendationsResponse
    {
        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $latestAttemptByExam = $this->latestSubmittedAttemptByExam($course, $enrollment->userId());

        return new LearningRecommendationsResponse(
            enrollmentId: $enrollment->id()->value(),
            nextLessonId: $this->nextLessonFor($course, $progress),
            weakCompetencies: $this->weakCompetenciesFor($latestAttemptByExam),
            retryableExams: $this->retryableExamsFor($course, $enrollment->userId(), $latestAttemptByExam),
        );
    }

    private function nextLessonFor(Course $course, EnrollmentProgress $progress): ?string
    {
        $completedLessonIds = $progress->completedLessonIds();
        $unlockStatus = $this->unlockCalculator->statusFor($course, $progress);

        foreach ($this->lessonCatalog->lessonIdsFor($course) as $lessonId) {
            if (in_array($lessonId, $completedLessonIds, true)) {
                continue;
            }

            $unitId = $unlockStatus->unitIdForLesson(LessonId::fromString($lessonId));
            if ($unitId !== null && $unlockStatus->isUnitUnlocked($unitId)) {
                return $lessonId;
            }
        }

        return null;
    }

    /**
     * @return array<string, ExamAttempt> Keyed by exam id.
     *
     * `ExamAttemptRepository::all()` returns attempts ordered by creation
     * time ascending, so simply overwriting per exam id as we iterate keeps
     * the most recently created (i.e. most recently submitted) attempt.
     */
    private function latestSubmittedAttemptByExam(Course $course, string $userId): array
    {
        $courseExamIds = array_map(
            static fn (Exam $exam): string => $exam->id()->value(),
            $this->exams->all($course->id()),
        );

        $latestByExam = [];
        foreach ($this->examAttempts->all(userId: $userId, status: ExamAttemptStatus::Submitted) as $attempt) {
            $examId = $attempt->examId()->value();
            if (! in_array($examId, $courseExamIds, true)) {
                continue;
            }

            $latestByExam[$examId] = $attempt;
        }

        return $latestByExam;
    }

    /**
     * @param  array<string, ExamAttempt>  $latestAttemptByExam
     * @return list<StudyRecommendationResponse>
     */
    private function weakCompetenciesFor(array $latestAttemptByExam): array
    {
        $recommendations = [];
        foreach ($latestAttemptByExam as $attempt) {
            $questionIdsByCompetency = [];
            foreach ($attempt->questionBreakdown() as $grade) {
                if ($grade->percentage() >= 100) {
                    continue;
                }

                $competencyId = $grade->competencyId()->value();
                $questionIdsByCompetency[$competencyId] ??= [];
                $questionIdsByCompetency[$competencyId][] = $grade->questionId()->value();
            }

            foreach ($attempt->competencyBreakdown() as $grade) {
                $competencyId = $grade->competencyId()->value();
                $questionIds = array_values(array_unique($questionIdsByCompetency[$competencyId] ?? []));
                if ($questionIds === []) {
                    continue;
                }

                $recommendations[] = new StudyRecommendationResponse(
                    $competencyId,
                    $grade->score(),
                    $grade->totalPoints(),
                    $grade->percentage(),
                    $questionIds,
                );
            }
        }

        usort($recommendations, static function (StudyRecommendationResponse $left, StudyRecommendationResponse $right): int {
            return [$left->percentage, $left->score, $left->competencyId] <=> [$right->percentage, $right->score, $right->competencyId];
        });

        return array_slice($recommendations, 0, self::MAX_WEAK_COMPETENCIES);
    }

    /**
     * @param  array<string, ExamAttempt>  $latestAttemptByExam
     * @return list<RetryableExamResponse>
     */
    private function retryableExamsFor(Course $course, string $userId, array $latestAttemptByExam): array
    {
        $retryable = [];
        foreach ($this->exams->all($course->id()) as $exam) {
            $examId = $exam->id()->value();
            $latestAttempt = $latestAttemptByExam[$examId] ?? null;
            if ($latestAttempt === null || $latestAttempt->passed()) {
                continue;
            }

            if ($this->examAttempts->findActiveFor($exam->id(), $userId) !== null) {
                continue;
            }

            $attemptsUsed = $this->examAttempts->countCompletedFor($exam->id(), $userId);
            if ($attemptsUsed >= $exam->maxAttempts()) {
                continue;
            }

            $retryable[] = new RetryableExamResponse(
                examId: $examId,
                title: $exam->title(),
                lastPercentage: $latestAttempt->percentage(),
                passingScore: $exam->passingScore(),
                attemptsUsed: $attemptsUsed,
                maxAttempts: $exam->maxAttempts(),
            );
        }

        return $retryable;
    }
}
