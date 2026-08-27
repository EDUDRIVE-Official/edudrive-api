<?php

declare(strict_types=1);

namespace Modules\FileStorage\Domain\Enums;

enum FileScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Infected = 'infected';
}
