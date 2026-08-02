<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\ArchivedCourseCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseAlreadyArchived;
use Modules\Academic\Domain\Exceptions\CourseAlreadyPublished;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

final class Course
{
    private function __construct(
        private readonly CourseId $id,
        private readonly CourseCode $code,
        private CourseTitle $title,
        private ?string $description,
        private ?string $objectives,
        private ?string $prerequisites,
        private ?CourseModality $modality,
        private ?int $durationHours,
        private CourseStatus $status,
        private ?DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $archivedAt,
    ) {}

    public static function create(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description = null,
        ?string $objectives = null,
        ?string $prerequisites = null,
        ?CourseModality $modality = null,
        ?int $durationHours = null,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeText($description),
            objectives: self::normalizeText($objectives),
            prerequisites: self::normalizeText($prerequisites),
            modality: $modality,
            durationHours: $durationHours,
            status: CourseStatus::Draft,
            publishedAt: null,
            archivedAt: null,
        );
    }

    public static function restore(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description,
        ?string $objectives,
        ?string $prerequisites,
        ?CourseModality $modality,
        ?int $durationHours,
        CourseStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeText($description),
            objectives: self::normalizeText($objectives),
            prerequisites: self::normalizeText($prerequisites),
            modality: $modality,
            durationHours: $durationHours,
            status: $status,
            publishedAt: $publishedAt,
            archivedAt: $archivedAt,
        );
    }

    public function rename(CourseTitle $title): void
    {
        $this->ensureIsNotArchived();

        $this->title = $title;
    }

    public function changeDescription(?string $description): void
    {
        $this->ensureIsNotArchived();

        $this->description = self::normalizeText($description);
    }

    public function publish(DateTimeImmutable $publishedAt): void
    {
        $this->ensureIsNotArchived();

        if ($this->status->isPublished()) {
            throw CourseAlreadyPublished::create();
        }

        $this->status = CourseStatus::Published;
        $this->publishedAt = $publishedAt;
    }

    public function archive(DateTimeImmutable $archivedAt): void
    {
        if ($this->status->isArchived()) {
            throw CourseAlreadyArchived::create();
        }

        $this->status = CourseStatus::Archived;
        $this->archivedAt = $archivedAt;
    }

    public function id(): CourseId
    {
        return $this->id;
    }

    public function code(): CourseCode
    {
        return $this->code;
    }

    public function title(): CourseTitle
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function objectives(): ?string
    {
        return $this->objectives;
    }

    public function prerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function modality(): ?CourseModality
    {
        return $this->modality;
    }

    public function durationHours(): ?int
    {
        return $this->durationHours;
    }

    public function status(): CourseStatus
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
            throw ArchivedCourseCannotBeModified::create();
        }
    }

    private static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
