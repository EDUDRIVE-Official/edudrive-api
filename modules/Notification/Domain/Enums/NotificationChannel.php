<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Web = 'web';
    case Mobile = 'mobile';
    case InternalMessage = 'internal_message';
}
