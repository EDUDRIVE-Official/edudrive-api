<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enums;

enum CommunicationTemplateStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
}
