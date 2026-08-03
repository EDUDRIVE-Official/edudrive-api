<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum ProgramContext: string
{
    case General = 'general';
    case Institutional = 'institutional';
    case Corporate = 'corporate';
}
