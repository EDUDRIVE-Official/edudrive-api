<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enums;

enum NotificationFrequency: string
{
    case Immediate = 'immediate';
    case Daily = 'daily';
    case Weekly = 'weekly';
}
