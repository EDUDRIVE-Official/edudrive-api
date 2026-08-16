<?php

declare(strict_types=1);

namespace Modules\Learning\Application\UseCases;

use DateTimeInterface;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\Repositories\LearningEventRepository;

final readonly class GetEnrollmentLearningEventsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private LearningEventRepository $events,
    ) {}

    public function handle(GetEnrollmentLearningEventsQuery $query): LearningEventResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($query->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        if ($enrollment->userId() !== $query->userId && ! $query->canViewOthers) {
            throw EnrollmentNotFound::withId($query->enrollmentId);
        }

        $events = $this->events->findByEnrollmentId($enrollment->id()->value());

        return new LearningEventResponse(
            enrollmentId: $enrollment->id()->value(),
            events: array_map(
                static fn (LearningEvent $event): array => [
                    'verb' => $event->verb()->value,
                    'subject_id' => $event->subjectId(),
                    'occurred_at' => $event->occurredAt()->format(DateTimeInterface::ATOM),
                    'evidence' => $event->evidence(),
                ],
                $events,
            ),
        );
    }
}
