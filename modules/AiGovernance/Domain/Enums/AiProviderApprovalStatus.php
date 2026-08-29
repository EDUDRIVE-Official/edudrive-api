<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiProviderApprovalStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RequiresReevaluation = 'requires_reevaluation';
}
