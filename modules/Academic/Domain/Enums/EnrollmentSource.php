<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum EnrollmentSource: string
{
    case Individual = 'individual';
    case Bulk = 'bulk';
    case Institutional = 'institutional';
}
