<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ExamKind: string
{
    case Standard = 'standard';
    case Theory = 'theory';
}
