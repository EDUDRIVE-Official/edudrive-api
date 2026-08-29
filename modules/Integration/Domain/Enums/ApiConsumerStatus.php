<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Enums;

enum ApiConsumerStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
}
