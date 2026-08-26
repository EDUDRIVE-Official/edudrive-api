<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final class Enrollment
{
    private function __construct(
        private EnrollmentId $id,
        private CourseId $courseId,
        private string $userId,
        private ?OrganizationId $organizationId,
        private EnrollmentStatus $status,
        private EnrollmentSource $source,
        private ?DateTimeImmutable $startsAt,
        private ?DateTimeImmutable $endsAt,
        private DateTimeImmutable $enrolledAt,
    ) {}

    public static function create(
        EnrollmentId $id,
        CourseId $courseId,
        string $userId,
        ?OrganizationId $organizationId = null,
        EnrollmentStatus $status = EnrollmentStatus::Pending,
        EnrollmentSource $source = EnrollmentSource::Individual,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?DateTimeImmutable $enrolledAt = null,
    ): self {
        $enrollment = new self(
            $id,
            $courseId,
            trim($userId),
            $organizationId,
            $status,
            $source,
            $startsAt,
            $endsAt,
            $enrolledAt ?? new DateTimeImmutable('now'),
        );
        $enrollment->assertValid();

        return $enrollment;
    }

    public static function restore(
        EnrollmentId $id,
        CourseId $courseId,
        string $userId,
        ?OrganizationId $organizationId,
        EnrollmentStatus $status,
        EnrollmentSource $source,
        ?DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        DateTimeImmutable $enrolledAt,
    ): self {
        return self::create($id, $courseId, $userId, $organizationId, $status, $source, $startsAt, $endsAt, $enrolledAt);
    }

    public function activate(): void
    {
        if ($this->status !== EnrollmentStatus::Pending) {
            throw InvalidEnrollment::create();
        }

        $this->status = EnrollmentStatus::Active;
    }

    public function complete(): void
    {
        if ($this->status !== EnrollmentStatus::Active) {
            throw InvalidEnrollment::create();
        }

        $this->status = EnrollmentStatus::Completed;
    }

    public function cancel(): void
    {
        if (! in_array($this->status, [EnrollmentStatus::Pending, EnrollmentStatus::Active], true)) {
            throw InvalidEnrollment::create();
        }

        $this->status = EnrollmentStatus::Canceled;
    }

    public function id(): EnrollmentId
    {
        return $this->id;
    }

    public function courseId(): CourseId
    {
        return $this->courseId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function organizationId(): ?OrganizationId
    {
        return $this->organizationId;
    }

    public function status(): EnrollmentStatus
    {
        return $this->status;
    }

    public function source(): EnrollmentSource
    {
        return $this->source;
    }

    public function startsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function enrolledAt(): DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    private function assertValid(): void
    {
        if ($this->userId === '') {
            throw InvalidEnrollment::create();
        }

        if ($this->source === EnrollmentSource::Institutional && $this->organizationId === null) {
            throw InvalidEnrollment::create();
        }

        if ($this->source !== EnrollmentSource::Institutional && $this->organizationId !== null) {
            throw InvalidEnrollment::create();
        }

        if ($this->startsAt !== null && $this->endsAt !== null && $this->endsAt < $this->startsAt) {
            throw InvalidEnrollment::create();
        }
    }
}
