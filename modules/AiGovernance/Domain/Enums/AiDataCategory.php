<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiDataCategory: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Personal = 'personal';
    case Minors = 'minors';
    case Sensitive = 'sensitive';
}
