<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Enrollment;

final readonly class EnrollmentResponse
{
    private function __construct(
        public string $id,
        public string $courseId,
        public string $userId,
        public ?string $organizationId,
        public string $status,
        public string $source,
        public ?string $startsAt,
        public ?string $endsAt,
        public string $enrolledAt,
    ) {}

    public static function fromEnrollment(Enrollment $enrollment): self
    {
        return new self(
            id: $enrollment->id()->value(),
            courseId: $enrollment->courseId()->value(),
            userId: $enrollment->userId(),
            organizationId: $enrollment->organizationId()?->value(),
            status: $enrollment->status()->value,
            source: $enrollment->source()->value,
            startsAt: $enrollment->startsAt()?->format(DATE_ATOM),
            endsAt: $enrollment->endsAt()?->format(DATE_ATOM),
            enrolledAt: $enrollment->enrolledAt()->format(DATE_ATOM),
        );
    }

    /** @return array{id: string, course_id: string, user_id: string, organization_id: string|null, status: string, source: string, starts_at: string|null, ends_at: string|null, enrolled_at: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->courseId,
            'user_id' => $this->userId,
            'organization_id' => $this->organizationId,
            'status' => $this->status,
            'source' => $this->source,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'enrolled_at' => $this->enrolledAt,
        ];
    }
}
