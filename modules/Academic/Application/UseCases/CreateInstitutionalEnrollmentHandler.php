<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Academic\Application\Commands\CreateInstitutionalEnrollmentCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final readonly class CreateInstitutionalEnrollmentHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private CourseRepository $courses,
    ) {}

    public function handle(CreateInstitutionalEnrollmentCommand $command): EnrollmentResponse
    {
        $courseId = CourseId::fromString($command->courseId);
        if ($this->courses->findById($courseId) === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        $existing = $this->enrollments->findActiveOrPendingFor($courseId, $command->userId);
        if ($existing !== null) {
            return EnrollmentResponse::fromEnrollment($existing);
        }

        try {
            $organizationId = OrganizationId::fromString($command->organizationId);
        } catch (InvalidArgumentException) {
            throw InvalidEnrollment::create();
        }

        $enrollment = Enrollment::create(
            id: EnrollmentId::fromString((string) Str::uuid()),
            courseId: $courseId,
            userId: $command->userId,
            organizationId: $organizationId,
            status: EnrollmentStatus::from($command->status),
            source: EnrollmentSource::Institutional,
            startsAt: $command->startsAt === null ? null : new DateTimeImmutable($command->startsAt),
            endsAt: $command->endsAt === null ? null : new DateTimeImmutable($command->endsAt),
            enrolledAt: new DateTimeImmutable('now'),
        );

        $this->enrollments->save($enrollment);

        return EnrollmentResponse::fromEnrollment($enrollment);
    }
}
