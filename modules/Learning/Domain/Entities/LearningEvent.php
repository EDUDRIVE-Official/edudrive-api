<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\Entities;

use DateTimeImmutable;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

final readonly class LearningEvent
{
    /** @param array<string, mixed> $evidence */
    private function __construct(
        private LearningEventId $id,
        private string $enrollmentId,
        private string $userId,
        private string $courseId,
        private LearningVerb $verb,
        private string $subjectId,
        private DateTimeImmutable $occurredAt,
        private array $evidence,
    ) {}

    /** @param array<string, mixed> $evidence */
    public static function create(
        LearningEventId $id,
        string $enrollmentId,
        string $userId,
        string $courseId,
        LearningVerb $verb,
        string $subjectId,
        DateTimeImmutable $occurredAt,
        array $evidence,
    ): self {
        return new self($id, $enrollmentId, $userId, $courseId, $verb, $subjectId, $occurredAt, $evidence);
    }

    public function id(): LearningEventId
    {
        return $this->id;
    }

    public function enrollmentId(): string
    {
        return $this->enrollmentId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function courseId(): string
    {
        return $this->courseId;
    }

    public function verb(): LearningVerb
    {
        return $this->verb;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /** @return array<string, mixed> */
    public function evidence(): array
    {
        return $this->evidence;
    }
}
