<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Exceptions\ScenarioPracticeIncomplete;
use Modules\Academic\Application\Exceptions\UnitLocked;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\ContentBlockType;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Certification\Application\Services\CertificateIssuer;
use Modules\Gamification\Application\Services\CourseCompletionRewarder;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\Notification\Application\Services\CourseCompletionNotifier;
use Modules\Notification\Application\Services\GuardianPracticeReadyNotifier;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Domain\Enums\EvidenceType;

final readonly class CompleteLessonHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progressRepository,
        private CourseRepository $courses,
        private CourseLessonCatalog $lessonCatalog,
        private CourseCurriculumUnlockCalculator $unlockCalculator,
        private EnrollmentProgressCalculator $calculator,
        private LearningEventRecorder $learningEvents,
        private ?RoadPassportEvidenceRecorder $passportEvidence = null,
        private ?CertificateIssuer $certificateIssuer = null,
        private ?CourseCompletionRewarder $completionRewarder = null,
        private ?CourseCompletionNotifier $completionNotifier = null,
        private ?GuardianPracticeReadyNotifier $practiceReadyNotifier = null,
    ) {}

    public function handle(CompleteLessonCommand $command): EnrollmentProgressResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($command->enrollmentId));
        if ($enrollment === null || $enrollment->userId() !== $command->userId) {
            throw EnrollmentNotFound::withId($command->enrollmentId);
        }

        if (! in_array($enrollment->status(), [EnrollmentStatus::Active, EnrollmentStatus::Completed], true)) {
            throw InvalidEnrollment::create();
        }

        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);

        $lessonId = LessonId::fromString($command->lessonId);
        $lesson = $this->lessonCatalog->lessonFor($course, $lessonId);
        if ($lesson === null) {
            throw LessonNotFound::withId($command->lessonId);
        }

        $scenarioResults = [];
        foreach ($lesson->blocks() as $block) {
            if ($block->type() !== ContentBlockType::Scenario) {
                continue;
            }

            $selectedId = $command->scenarioAnswers[$block->id()->value()] ?? null;
            $correctChoice = null;
            foreach ($block->payload()['choices'] as $choice) {
                if (is_array($choice) && ($choice['correct'] ?? false) === true) {
                    $correctChoice = $choice;
                    break;
                }
            }

            if ($selectedId === null || ! is_array($correctChoice) || $selectedId !== $correctChoice['id']) {
                throw ScenarioPracticeIncomplete::create();
            }

            $scenarioResults[] = ['block_id' => $block->id()->value(), 'choice_id' => $selectedId, 'correct' => true];
        }

        $progress = $this->progressRepository->findByEnrollmentId($enrollment->id());

        $unlockStatus = $this->unlockCalculator->statusFor($course, $progress);
        $unitId = $unlockStatus->unitIdForLesson($lessonId);
        if ($unitId !== null && ! $unlockStatus->isUnitUnlocked($unitId)) {
            throw UnitLocked::withId($unitId->value());
        }

        $progress->completeLesson($lessonId, new DateTimeImmutable('now'), $command->timeSpentMinutes);
        $this->progressRepository->save($progress);

        $learningDesign = $lesson->learningDesign();
        $this->learningEvents->record(new LearningEventEntry(
            enrollmentId: $enrollment->id()->value(),
            userId: $enrollment->userId(),
            courseId: $enrollment->courseId()->value(),
            verb: LearningVerb::LessonCompleted,
            subjectId: $lessonId->value(),
            evidence: [
                'lesson_code' => $lesson->code()->value(),
                'lesson_title' => $lesson->title(),
                'time_spent_minutes' => $command->timeSpentMinutes,
                'scenario_results' => $scenarioResults,
                'evidence_scope' => 'formative_completion',
                'demonstrates_mastery' => false,
                'indicator_codes' => $learningDesign->indicatorCodes ?? [],
                'learning_design_version' => $learningDesign?->version,
                'jurisdictions' => $learningDesign->jurisdictions ?? [],
                'reflection' => $command->reflection,
                'self_assessment' => $command->selfAssessment,
            ],
        ));

        $this->passportEvidence?->record(new EvidenceEntry(
            userId: $enrollment->userId(),
            type: EvidenceType::LessonCompleted,
            subjectId: $lessonId->value(),
            courseId: $enrollment->courseId()->value(),
            details: [
                'lesson_code' => $lesson->code()->value(),
                'lesson_title' => $lesson->title(),
                'stage' => $learningDesign?->stage,
                'competency_id' => $learningDesign?->competencyId,
                'subcompetency_code' => $learningDesign?->subcompetencyCode,
                'indicator_codes' => $learningDesign->indicatorCodes ?? [],
                'learning_design_version' => $learningDesign?->version,
                'jurisdictions' => $learningDesign->jurisdictions ?? [],
                'scenario_results' => $scenarioResults,
                'evidence_scope' => 'formative_completion',
                'demonstrates_mastery' => false,
                'reflection' => $command->reflection,
                'self_assessment' => $command->selfAssessment,
            ],
        ));

        if ($learningDesign?->requiresGuardian === true) {
            $this->practiceReadyNotifier?->notifyForMinor($enrollment->userId(), $lessonId->value(), $lesson->title());
        }

        $result = $this->calculator->calculate($enrollment, $progress);

        if (
            $result->totalLessons > 0
            && $result->completedLessonsCount === $result->totalLessons
            && $enrollment->status() === EnrollmentStatus::Active
        ) {
            $enrollment->complete();
            $this->enrollments->save($enrollment);

            $this->passportEvidence?->record(new EvidenceEntry(
                userId: $enrollment->userId(),
                type: EvidenceType::CourseCompleted,
                subjectId: $enrollment->id()->value(),
                courseId: $enrollment->courseId()->value(),
                details: [
                    'course_title' => $course->title()->value(),
                    'completed_lessons' => $result->completedLessonsCount,
                ],
            ));

            $this->certificateIssuer?->issue(
                $enrollment->userId(),
                $enrollment->courseId()->value(),
            );

            $this->completionRewarder?->reward(
                $enrollment->userId(),
                $enrollment->courseId()->value(),
                $learningDesign?->competencyId,
            );

            $this->completionNotifier?->notify(
                $enrollment->userId(),
                $enrollment->courseId()->value(),
                $course->title()->value(),
            );
        }

        return $result;
    }
}
