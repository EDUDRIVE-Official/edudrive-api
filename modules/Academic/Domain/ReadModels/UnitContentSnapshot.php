<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ReadModels;

use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Enums\CourseStatus;

final readonly class UnitContentSnapshot
{
    public function __construct(
        private CourseStatus $courseStatus,
        private UnitContent $content,
    ) {}

    public function courseStatus(): CourseStatus
    {
        return $this->courseStatus;
    }

    public function content(): UnitContent
    {
        return $this->content;
    }
}
