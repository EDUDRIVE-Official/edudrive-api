<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\RecordGuardianPracticeObservationCommand;
use Modules\Identity\Application\Exceptions\GuardianRelationshipNotFound;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Notification\Application\Services\GuardianPracticeNotifier;
use Modules\Notification\Application\Services\GuardianPracticeReadyNotificationResolver;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Domain\Enums\EvidenceType;

final readonly class RecordGuardianPracticeObservationHandler
{
    public function __construct(
        private GuardianRelationshipRepository $relationships,
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progress,
        private CourseRepository $courses,
        private CourseLessonCatalog $lessons,
        private RoadPassportEvidenceRecorder $evidence,
        private AuditLogger $audit,
        private GuardianPracticeNotifier $notifier,
        private GuardianPracticeReadyNotificationResolver $readyNotificationResolver,
    ) {}

    public function handle(RecordGuardianPracticeObservationCommand $command): void
    {
        $relationship = $this->relationships->findActiveByGuardianAndMinor($command->guardianUserId, $command->minorUserId);
        if ($relationship === null) {
            throw new GuardianRelationshipNotFound;
        }

        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($command->enrollmentId));
        if ($enrollment === null || $enrollment->userId() !== $command->minorUserId) {
            throw EnrollmentNotFound::withId($command->enrollmentId);
        }

        $course = $this->courses->findById($enrollment->courseId());
        assert($course instanceof Course);
        $lessonId = LessonId::fromString($command->lessonId);
        $lesson = $this->lessons->lessonFor($course, $lessonId);
        $completed = $this->progress->findByEnrollmentId($enrollment->id())->completedLessonIds();
        if ($lesson === null
            || $lesson->learningDesign()?->requiresGuardian !== true
            || ! in_array($command->lessonId, $completed, true)) {
            throw LessonNotFound::withId($command->lessonId);
        }

        $design = $lesson->learningDesign();
        $subjectId = hash('sha256', $command->guardianUserId.'|'.$command->lessonId);
        $this->evidence->record(new EvidenceEntry(
            userId: $command->minorUserId,
            type: EvidenceType::GuidedPracticeObserved,
            subjectId: $subjectId,
            courseId: $enrollment->courseId()->value(),
            details: [
                'lesson_id' => $command->lessonId,
                'lesson_title' => $lesson->title(),
                'behavior' => $command->behavior,
                'observation' => $command->note,
                'practice_context' => $command->practiceContext,
                'observer_role' => 'guardian',
                'guardian_relationship_id' => $relationship->id(),
                'safe_environment_confirmed' => true,
                'competency_id' => $design->competencyId,
                'indicator_codes' => $design->indicatorCodes,
            ],
        ));

        $this->audit->log(new AuditEntry(
            action: 'identity.guardian_practice_observed',
            userId: $command->guardianUserId,
            entity: 'Lesson',
            entityId: $command->lessonId,
            metadata: ['minor_user_id' => $command->minorUserId, 'enrollment_id' => $command->enrollmentId],
        ));

        $this->notifier->notify($command->minorUserId, $command->lessonId, $lesson->title(), $subjectId);
        $this->readyNotificationResolver->resolve(
            $command->guardianUserId,
            $command->minorUserId,
            $command->lessonId,
        );
    }
}
