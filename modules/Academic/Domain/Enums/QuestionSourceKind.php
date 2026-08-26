<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum QuestionSourceKind: string
{
    case Custom = 'custom';
    case Official = 'official';
}
