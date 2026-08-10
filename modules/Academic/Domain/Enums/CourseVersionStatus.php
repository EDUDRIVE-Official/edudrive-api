<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseVersionStatus: string
{
    case Published = 'published';
    case Archived = 'archived';

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }
}
