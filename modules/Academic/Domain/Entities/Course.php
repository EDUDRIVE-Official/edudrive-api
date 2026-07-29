<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\ValueObjects\CourseCode;

final class Course
{
    public function __construct(
        private readonly CourseCode $code,
        private string $name,
        private ?string $description,
        private CourseStatus $status = CourseStatus::Draft,
    ) {
    }

    public function code(): CourseCode
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

    public function status(): CourseStatus
    {
        return $this->status;
    }

    public function rename(string $name): void
    {
        $this->name = trim($name);
    }

    public function changeDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function publish(): void
    {
        $this->status = CourseStatus::Published;
    }

    public function archive(): void
    {
        $this->status = CourseStatus::Archived;
    }
}