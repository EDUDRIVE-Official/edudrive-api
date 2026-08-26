<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\CompleteEnrollmentCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Domain\Enums\EvidenceType;

final readonly class CompleteEnrollmentHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private ?RoadPassportEvidenceRecorder $evidenceRecorder = null,
    ) {}

    public function handle(CompleteEnrollmentCommand $command): EnrollmentResponse
    {
        $enrollment = $this->enrollments->findById(EnrollmentId::fromString($command->enrollmentId));
        if ($enrollment === null) {
            throw EnrollmentNotFound::withId($command->enrollmentId);
        }

        $enrollment->complete();
        $this->enrollments->save($enrollment);

        $this->evidenceRecorder?->record(new EvidenceEntry(
            userId: $enrollment->userId(),
            type: EvidenceType::CourseCompleted,
            subjectId: $enrollment->id()->value(),
            courseId: $enrollment->courseId()->value(),
            details: [],
        ));

        return EnrollmentResponse::fromEnrollment($enrollment);
    }
}
