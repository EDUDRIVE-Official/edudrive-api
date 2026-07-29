<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Deprecated = 'deprecated';
}