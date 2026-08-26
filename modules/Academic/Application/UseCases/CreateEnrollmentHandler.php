<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\EnrollmentAlreadyExists;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final readonly class CreateEnrollmentHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private CourseRepository $courses,
    ) {}

    public function handle(CreateEnrollmentCommand $command): EnrollmentResponse
    {
        $courseId = CourseId::fromString($command->courseId);
        if ($this->courses->findById($courseId) === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        if ($this->enrollments->findActiveOrPendingFor($courseId, $command->userId) !== null) {
            throw EnrollmentAlreadyExists::create();
        }

        $enrollment = Enrollment::create(
            id: EnrollmentId::fromString((string) Str::uuid()),
            courseId: $courseId,
            userId: $command->userId,
            status: EnrollmentStatus::from($command->status),
            source: EnrollmentSource::from($command->source),
            startsAt: $command->startsAt === null ? null : new DateTimeImmutable($command->startsAt),
            endsAt: $command->endsAt === null ? null : new DateTimeImmutable($command->endsAt),
            enrolledAt: new DateTimeImmutable('now'),
        );

        $this->enrollments->save($enrollment);

        return EnrollmentResponse::fromEnrollment($enrollment);
    }
}
