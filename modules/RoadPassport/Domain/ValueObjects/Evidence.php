<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\RoadPassport\Domain\Enums\EvidenceType;

final readonly class Evidence
{
    /** @param array<string, mixed> $details */
    private function __construct(
        public EvidenceType $type,
        public string $subjectId,
        public string $courseId,
        public DateTimeImmutable $occurredAt,
        public array $details,
    ) {}

    /** @param array<string, mixed> $details */
    public static function create(
        EvidenceType $type,
        string $subjectId,
        string $courseId,
        DateTimeImmutable $occurredAt,
        array $details,
    ): self {
        return new self($type, $subjectId, $courseId, $occurredAt, $details);
    }

    public function sameSubjectAs(self $other): bool
    {
        return $this->type === $other->type && $this->subjectId === $other->subjectId;
    }
}
