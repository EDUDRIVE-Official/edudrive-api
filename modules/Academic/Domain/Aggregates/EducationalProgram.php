<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Academic\Domain\Entities\ProgramCourse;
use Modules\Academic\Domain\Enums\ProgramStatus;
use Modules\Academic\Domain\Exceptions\ArchivedProgramCannotBeModified;
use Modules\Academic\Domain\Exceptions\ProgramRequiresCourses;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final class EducationalProgram
{
    /** @param list<ProgramCourse> $courses */
    private function __construct(
        private readonly ProgramId $id,
        private readonly ProgramCode $code,
        private readonly string $name,
        private readonly ?string $description,
        private ProgramAudience $audience,
        private array $courses,
        private ProgramStatus $status,
        private ?DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $archivedAt,
    ) {}

    public static function create(
        ProgramId $id,
        ProgramCode $code,
        string $name,
        ?string $description,
        ProgramAudience $audience,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: $name,
            description: $description,
            audience: $audience,
            courses: [],
            status: ProgramStatus::Draft,
            publishedAt: null,
            archivedAt: null,
        );
    }

    /** @param list<ProgramCourse> $courses */
    public static function restore(
        ProgramId $id,
        ProgramCode $code,
        string $name,
        ?string $description,
        ProgramAudience $audience,
        array $courses,
        ProgramStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: $name,
            description: $description,
            audience: $audience,
            courses: $courses,
            status: $status,
            publishedAt: $publishedAt,
            archivedAt: $archivedAt,
        );
    }

    public function changeAudience(ProgramAudience $audience): void
    {
        $this->ensureIsNotArchived();

        $this->audience = $audience;
    }

    /** @param list<CourseId> $courseIds */
    public function replaceCourses(array $courseIds): void
    {
        $this->ensureIsNotArchived();

        $seen = [];

        foreach ($courseIds as $courseId) {
            if (isset($seen[$courseId->value()])) {
                throw new InvalidArgumentException('Un curso no puede aparecer mas de una vez en el programa.');
            }

            $seen[$courseId->value()] = true;
        }

        $courses = [];

        foreach ($courseIds as $index => $courseId) {
            $courses[] = ProgramCourse::create($courseId, $index + 1);
        }

        $this->courses = $courses;
    }

    public function publish(DateTimeImmutable $publishedAt): void
    {
        $this->ensureIsNotArchived();

        if ($this->courses === []) {
            throw ProgramRequiresCourses::create();
        }

        $this->status = ProgramStatus::Published;
        $this->publishedAt = $publishedAt;
    }

    public function archive(DateTimeImmutable $archivedAt): void
    {
        $this->ensureIsNotArchived();

        $this->status = ProgramStatus::Archived;
        $this->archivedAt = $archivedAt;
    }

    public function id(): ProgramId
    {
        return $this->id;
    }

    public function code(): ProgramCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function audience(): ProgramAudience
    {
        return $this->audience;
    }

    /** @return list<ProgramCourse> */
    public function courses(): array
    {
        return $this->courses;
    }

    public function status(): ProgramStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    private function ensureIsNotArchived(): void
    {
        if ($this->status->isArchived()) {
            throw ArchivedProgramCannotBeModified::create();
        }
    }
}
