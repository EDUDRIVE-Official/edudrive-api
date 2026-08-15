<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\Exceptions\InvalidLessonCompletion;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

final class EnrollmentProgress
{
    /** @var list<LessonCompletion> */
    private array $lessonCompletions;

    /** @param list<LessonCompletion> $lessonCompletions */
    private function __construct(
        private readonly EnrollmentId $enrollmentId,
        array $lessonCompletions,
    ) {
        $this->lessonCompletions = $lessonCompletions;
    }

    public static function create(EnrollmentId $enrollmentId): self
    {
        self::assertNoDuplicateLessons([]);

        return new self($enrollmentId, []);
    }

    /** @param list<LessonCompletion> $lessonCompletions */
    public static function restore(EnrollmentId $enrollmentId, array $lessonCompletions): self
    {
        self::assertNoDuplicateLessons($lessonCompletions);

        return new self($enrollmentId, $lessonCompletions);
    }

    public function enrollmentId(): EnrollmentId
    {
        return $this->enrollmentId;
    }

    /** @return list<LessonCompletion> */
    public function lessonCompletions(): array
    {
        return $this->lessonCompletions;
    }

    public function completeLesson(LessonId $lessonId, DateTimeImmutable $completedAt, ?int $timeSpentMinutes): void
    {
        foreach ($this->lessonCompletions as $index => $completion) {
            if ($completion->lessonId()->equals($lessonId)) {
                $this->lessonCompletions[$index] = $completion->withCompletion($completedAt, $timeSpentMinutes);

                return;
            }
        }

        $this->lessonCompletions[] = LessonCompletion::create($lessonId, $completedAt, $timeSpentMinutes);
    }

    /** @return list<string> */
    public function completedLessonIds(): array
    {
        return array_map(
            static fn (LessonCompletion $completion): string => $completion->lessonId()->value(),
            $this->lessonCompletions,
        );
    }

    public function totalTimeSpentMinutes(): int
    {
        return array_sum(array_map(
            static fn (LessonCompletion $completion): int => $completion->timeSpentMinutes() ?? 0,
            $this->lessonCompletions,
        ));
    }

    public function lastCompletedAt(): ?DateTimeImmutable
    {
        if ($this->lessonCompletions === []) {
            return null;
        }

        $latest = $this->lessonCompletions[0]->completedAt();
        foreach ($this->lessonCompletions as $completion) {
            if ($completion->completedAt() > $latest) {
                $latest = $completion->completedAt();
            }
        }

        return $latest;
    }

    /** @param list<LessonCompletion> $lessonCompletions */
    private static function assertNoDuplicateLessons(array $lessonCompletions): void
    {
        $seen = [];
        foreach ($lessonCompletions as $completion) {
            $lessonId = $completion->lessonId()->value();
            if (isset($seen[$lessonId])) {
                throw InvalidLessonCompletion::duplicateLesson();
            }
            $seen[$lessonId] = true;
        }
    }
}
