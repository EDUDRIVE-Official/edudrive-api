<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ExamFeedbackMode: string
{
    case None = 'none';
    case AfterSubmission = 'after_submission';
    case Immediate = 'immediate';
}
