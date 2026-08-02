<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseModality: string
{
    case InPerson = 'in_person';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';
}
