<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Canceled = 'canceled';
}
