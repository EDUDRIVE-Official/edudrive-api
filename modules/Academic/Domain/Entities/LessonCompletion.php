<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use DateTimeImmutable;
use Modules\Academic\Domain\Exceptions\InvalidLessonCompletion;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class LessonCompletion
{
    private function __construct(
        private LessonId $lessonId,
        private DateTimeImmutable $completedAt,
        private ?int $timeSpentMinutes,
    ) {}

    public static function create(LessonId $lessonId, DateTimeImmutable $completedAt, ?int $timeSpentMinutes): self
    {
        if ($timeSpentMinutes !== null && $timeSpentMinutes < 0) {
            throw InvalidLessonCompletion::create();
        }

        return new self($lessonId, $completedAt, $timeSpentMinutes);
    }

    public function lessonId(): LessonId
    {
        return $this->lessonId;
    }

    public function completedAt(): DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function timeSpentMinutes(): ?int
    {
        return $this->timeSpentMinutes;
    }

    public function withCompletedAt(DateTimeImmutable $completedAt, ?int $timeSpentMinutes): self
    {
        return self::create($this->lessonId, $completedAt, $timeSpentMinutes);
    }
}
