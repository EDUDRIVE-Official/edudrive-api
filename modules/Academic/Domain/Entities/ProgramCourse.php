<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use InvalidArgumentException;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ProgramCourse
{
    private function __construct(
        private CourseId $courseId,
        private int $position,
    ) {}

    public static function create(CourseId $courseId, int $position): self
    {
        if ($position < 1) {
            throw new InvalidArgumentException('La posicion del curso debe iniciar en uno.');
        }

        return new self($courseId, $position);
    }

    public function courseId(): CourseId
    {
        return $this->courseId;
    }

    public function position(): int
    {
        return $this->position;
    }
}
