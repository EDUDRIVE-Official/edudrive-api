<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiDecisionReviewStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
