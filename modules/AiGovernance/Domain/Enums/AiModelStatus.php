<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiModelStatus: string
{
    case Registered = 'registered';
    case Approved = 'approved';
    case Deprecated = 'deprecated';
    case Retired = 'retired';
}
