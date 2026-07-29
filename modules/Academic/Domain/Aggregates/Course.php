<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
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
        private CourseStatus $status,
        private ?DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $archivedAt,
    ) {}

    public static function create(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description = null,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeDescription($description),
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
        CourseStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeDescription($description),
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

        $this->description = self::normalizeDescription($description);
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

    private static function normalizeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $description = trim($description);

        return $description === '' ? null : $description;
    }
}
