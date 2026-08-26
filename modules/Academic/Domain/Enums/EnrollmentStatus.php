<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
