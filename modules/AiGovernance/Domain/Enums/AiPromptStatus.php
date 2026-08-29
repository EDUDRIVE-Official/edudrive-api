<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiPromptStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Retired = 'retired';
}
